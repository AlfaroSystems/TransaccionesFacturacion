<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\InventoryLocation;
use App\Models\InventoryLocationDetail;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\InventoryTransferDiscrepancy;
use App\Models\Location;
use App\Models\Scopes\BranchScope;
use App\Models\Vehicle;
use App\Models\Warehouse;
use App\Services\InventoryTransferService;
use App\Support\BranchAccess;
use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Traslados de lotes entre bodegas: Casa Matriz reparte a las sucursales lo que entró con las
 * compras. Cada paso del flujo (autorizar, preparar, despachar al vehículo, recibir, registrar
 * discrepancias y regularizar) es una acción; la lógica está en InventoryTransferService.
 */
class InventoryTransferController extends Controller
{
    private const MENSAJES = [
        'details.required' => 'Indique la cantidad solicitada de al menos un lote.',
        'id_destination_warehouse.different' => 'La bodega destino debe ser distinta de la de origen.',
        'id_destination_warehouse.in' => 'La bodega destino debe ser de la misma empresa que la de origen.',
        'id_source_warehouse.in' => 'Elija una bodega de origen de su sucursal.',
        'details.*.id_source_inventory_location.in' => 'La ubicación de origen debe ser de la bodega origen.',
        'details.*.id_destination_inventory_location.in' => 'La ubicación destino debe ser de la bodega destino.',
        'id_vehicle.in' => 'Elija un vehículo activo.',
        'id_driver_employee.in' => 'Elija un motorista activo.',
    ];

    public function __construct(private readonly InventoryTransferService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('inventory.ver');

        $status = array_key_exists((string) $request->input('status'), InventoryTransfer::STATUS_LABELS) ? $request->input('status') : null;
        $search = trim((string) $request->input('search'));

        $transfers = InventoryTransfer::query()
            ->with(['sourceWarehouse', 'destinationWarehouse', 'user', 'vehicle', 'driver'])
            ->withCount('details')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where('transfer_code', 'ilike', "%{$search}%"))
            ->orderByDesc('id_inventory_transfer')
            ->paginate(15)
            ->withQueryString();

        return view('inventory_transfers.index', compact('transfers', 'status', 'search'));
    }

    /**
     * Primero se eligen las bodegas (?id_source_warehouse=&id_destination_warehouse=); luego se
     * muestra la existencia de la bodega origen para indicar cuánto sale de cada lote.
     */
    public function create(Request $request)
    {
        Gate::authorize('inventory.trasladar');

        $origenes = $this->bodegasOrigen();
        $origen = $origenes->firstWhere('id_warehouse', $request->integer('id_source_warehouse'));
        $destinos = $this->bodegasDestino($origen);
        $destino = $destinos->firstWhere('id_warehouse', $request->integer('id_destination_warehouse'));

        return view('inventory_transfers.form', [
            'transfer' => null,
            'origenes' => $origenes,
            'destinos' => $destinos,
            'origen' => $origen,
            'destino' => $destino,
            ...($origen && $destino ? $this->datosDeLineas($origen, $destino) : []),
        ]);
    }

    public function store(Request $request)
    {
        Gate::authorize('inventory.trasladar');

        $origen = $this->bodegasOrigen()->firstWhere('id_warehouse', $request->integer('id_source_warehouse'));
        $validated = $this->validar($request, $origen?->id_warehouse, $request->integer('id_destination_warehouse') ?: null, $origen);

        $transfer = $this->service->crear($validated);

        return redirect()
            ->route('inventory-transfers.show', $transfer)
            ->with('success', "Traslado {$transfer->transfer_code} guardado como borrador.");
    }

    public function show(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.ver');

        $this->cargarDetalle($transfer);

        return view('inventory_transfers.show', [
            'transfer' => $transfer,
            'ubicacionesDestino' => $this->ubicacionesDeBodega($transfer->id_destination_warehouse),
            'espacios' => $transfer->status === InventoryTransfer::STATUS_PREPARING ? $this->service->espaciosDelVehiculo($transfer) : collect(),
            'seguimiento' => $this->seguimiento($transfer),
        ]);
    }

    /**
     * Lista de preparación para imprimir: qué sacar de cada ubicación de la bodega origen.
     */
    public function picking(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        if (! in_array($transfer->status, [InventoryTransfer::STATUS_AUTHORIZED, InventoryTransfer::STATUS_PREPARING], true)) {
            return redirect()->route('inventory-transfers.show', $transfer)->with('error', 'La lista de preparación es de un traslado autorizado o en preparación.');
        }

        $this->cargarDetalle($transfer);

        return view('inventory_transfers.picking', compact('transfer'));
    }

    public function edit(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        if (! $transfer->isDraft()) {
            return redirect()->route('inventory-transfers.show', $transfer)->with('error', 'Solo se pueden editar traslados en borrador.');
        }

        $transfer->load(['sourceWarehouse', 'destinationWarehouse', 'details']);

        return view('inventory_transfers.form', [
            'transfer' => $transfer,
            'origenes' => collect([$transfer->sourceWarehouse]),
            'destinos' => collect([$transfer->destinationWarehouse]),
            'origen' => $transfer->sourceWarehouse,
            'destino' => $transfer->destinationWarehouse,
            ...$this->datosDeLineas($transfer->sourceWarehouse, $transfer->destinationWarehouse, $transfer),
        ]);
    }

    public function update(Request $request, InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        // Las bodegas no cambian: para otras, se crea otro traslado
        $request->merge([
            'id_source_warehouse' => $transfer->id_source_warehouse,
            'id_destination_warehouse' => $transfer->id_destination_warehouse,
        ]);
        $validated = $this->validar($request, $transfer->id_source_warehouse, $transfer->id_destination_warehouse, $transfer->sourceWarehouse()->first(), $transfer);

        try {
            $this->service->actualizar($transfer, $validated);
        } catch (\InvalidArgumentException $e) {
            return redirect()->route('inventory-transfers.show', $transfer)->with('error', $e->getMessage());
        }

        return redirect()->route('inventory-transfers.show', $transfer)->with('success', 'Traslado actualizado.');
    }

    public function destroy(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        try {
            $this->service->eliminar($transfer);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return redirect()->route('inventory-transfers.index')->with('success', 'Traslado eliminado.');
    }

    public function submit(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        return $this->accion(fn () => $this->service->enviarAAutorizacion($transfer), 'Traslado enviado a autorización.');
    }

    public function authorizeTransfer(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.autorizar_traslados');

        return $this->accion(fn () => $this->service->autorizar($transfer), 'Traslado autorizado: ya se puede preparar su despacho.');
    }

    public function reject(Request $request, InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.autorizar_traslados');

        $validated = $request->validate(
            ['status_reason' => ['required', 'string', 'max:500']],
            ['status_reason.required' => 'Indique el motivo del rechazo.']
        );

        return $this->accion(fn () => $this->service->rechazar($transfer, $validated['status_reason']), 'Traslado rechazado: volvió a borrador con el motivo.');
    }

    public function prepare(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        return $this->accion(fn () => $this->service->prepararDespacho($transfer), 'Despacho en preparación: indique lo preparado y confirme el despacho.');
    }

    public function dispatchTransfer(Request $request, InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        $validated = $request->validate([
            'id_vehicle_location' => ['required', 'integer'],
            'prepared' => ['nullable', 'array'],
            'prepared.*' => ['nullable', 'numeric', 'min:0'],
        ], ['id_vehicle_location.required' => 'Elija el espacio del vehículo donde va la mercadería.']);

        // Sin cantidad, se despacha lo solicitado
        $preparado = array_filter($validated['prepared'] ?? [], fn ($valor) => $valor !== null);

        return $this->accion(
            fn () => $this->service->despachar($transfer, $preparado, (int) $validated['id_vehicle_location']),
            'Traslado despachado: la mercadería salió de la bodega origen y va en el vehículo.'
        );
    }

    public function cancel(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        return $this->accion(fn () => $this->service->cancelar($transfer), 'Traslado cancelado.');
    }

    public function receive(Request $request, InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.recibir_traslados');

        $validated = $request->validate([
            'received' => ['nullable', 'array'],
            'received.*.quantity_received' => ['nullable', 'numeric', 'min:0'],
            'received.*.id_destination_inventory_location' => ['nullable', 'integer', Rule::in($this->ubicacionesDeBodega($transfer->id_destination_warehouse)->pluck('id_inventory_location')->all())],
        ], ['received.*.id_destination_inventory_location.in' => 'La ubicación debe ser de la bodega destino.']);

        // Sin cantidad, se recibe todo lo que sigue en el vehículo (0 es una cantidad: no llegó
        // nada). Sin ubicación, la que se indicó en el borrador
        $recibido = collect($validated['received'] ?? [])
            ->map(fn ($linea) => array_filter([
                'quantity_received' => $linea['quantity_received'] ?? null,
                'id_destination_inventory_location' => ($linea['id_destination_inventory_location'] ?? null) ?: null,
            ], fn ($valor) => $valor !== null))
            ->all();

        try {
            $this->service->recibir($transfer, $recibido);
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        $mensajes = [
            InventoryTransfer::STATUS_RECEIVED => 'Traslado recibido completo: su mercadería ya está en la bodega destino.',
            InventoryTransfer::STATUS_PARTIALLY_RECEIVED => 'Recepción parcial registrada: lo que falta sigue en el vehículo.',
            InventoryTransfer::STATUS_WITH_DISCREPANCY => 'Recepción registrada con discrepancias: regularícelas para cerrar el traslado.',
        ];

        return back()->with('success', $mensajes[$transfer->fresh()->status] ?? 'Recepción registrada.');
    }

    public function continueTransit(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.recibir_traslados');

        return $this->accion(fn () => $this->service->continuarEnTransito($transfer), 'El traslado sigue en tránsito: lo que falta se recibirá después.');
    }

    /**
     * Formulario de faltantes, daños y sobrantes de cada línea.
     */
    public function discrepancies(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.recibir_traslados');

        if (! $transfer->isReceivable()) {
            return redirect()->route('inventory-transfers.show', $transfer)->with('error', 'Las discrepancias se registran al recibir un traslado en tránsito.');
        }

        $this->cargarDetalle($transfer);

        return view('inventory_transfers.discrepancies', [
            'transfer' => $transfer,
            'ubicacionesDestino' => $this->ubicacionesDeBodega($transfer->id_destination_warehouse),
        ]);
    }

    public function storeDiscrepancies(Request $request, InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.recibir_traslados');

        $validated = $request->validate([
            'discrepancies' => ['nullable', 'array'],
            'discrepancies.*.shortage' => ['nullable', 'numeric', 'min:0'],
            'discrepancies.*.damage' => ['nullable', 'numeric', 'min:0'],
            'discrepancies.*.surplus' => ['nullable', 'numeric', 'min:0'],
            'discrepancies.*.id_destination_inventory_location' => ['nullable', 'integer', Rule::in($this->ubicacionesDeBodega($transfer->id_destination_warehouse)->pluck('id_inventory_location')->all())],
            'discrepancies.*.notes' => ['nullable', 'string', 'max:500'],
        ], ['discrepancies.*.id_destination_inventory_location.in' => 'La ubicación debe ser de la bodega destino.']);

        try {
            $this->service->registrarDiscrepancias($transfer, $validated['discrepancies'] ?? []);
        } catch (\InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()
            ->route('inventory-transfers.show', $transfer)
            ->with('success', 'Discrepancias registradas: quien autoriza los traslados las regulariza para cerrarlo.');
    }

    public function regularize(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.regularizar_traslados');

        return $this->accion(fn () => $this->service->regularizar($transfer), 'Existencias regularizadas: el traslado quedó recibido y cerrado.');
    }

    /**
     * "1 unidad", "2.5 unidades".
     */
    public static function unidades(float $cantidad): string
    {
        $texto = rtrim(rtrim(number_format($cantidad, 4, '.', ''), '0'), '.');

        return $texto.' '.($texto === '1' ? 'unidad' : 'unidades');
    }

    /**
     * Relaciones que muestran el detalle, la lista de preparación y las discrepancias.
     */
    private function cargarDetalle(InventoryTransfer $transfer): void
    {
        $transfer->load([
            'sourceWarehouse.branch' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
            'destinationWarehouse.branch' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
            'vehicle', 'driver', 'user', 'authorizedBy', 'vehicleLocation.warehouseAllBranches',
            'details' => fn ($q) => $q->orderBy('id_inventory_transfer_detail'),
            'details.lot', 'details.product.purchaseUnit', 'details.sourceLocation', 'details.destinationLocation',
            'details.discrepancies.destinationLocation', 'details.discrepancies.user',
        ]);
    }

    /**
     * Seguimiento del traslado: cada paso con su fecha y quién lo hizo, del más antiguo al más
     * reciente. Las recepciones salen de sus movimientos (una por fecha y usuario); los envíos a
     * autorización, los rechazos y quién hizo cada paso, de los cambios de estado de la bitácora.
     *
     * @return Collection<int, object{orden: int, fecha: CarbonInterface, titulo: string, detalle: ?string}>
     */
    private function seguimiento(InventoryTransfer $transfer): Collection
    {
        $cambios = AuditLog::query()
            ->with('user')
            ->where('auditable_type', $transfer->getMorphClass())
            ->where('id_record', $transfer->id_inventory_transfer)
            ->orderBy('id_log')
            ->get()
            ->filter(fn (AuditLog $log) => isset($log->modified_data['status']))
            ->values();

        // Quién pasó el traslado de un estado a otro (la última vez)
        $quien = fn (array $desde, string $hacia) => $cambios->last(
            fn (AuditLog $log) => $log->modified_data['status'] === $hacia && in_array($log->original_data['status'] ?? null, $desde, true)
        )?->user?->username;

        // "orden" desempata pasos del mismo segundo según el flujo
        $pasos = collect([(object) [
            'orden' => 0,
            'fecha' => $transfer->created_at,
            'titulo' => 'Traslado creado',
            'detalle' => $transfer->user?->username,
        ]]);

        foreach ($cambios as $log) {
            $anterior = $log->original_data['status'] ?? null;
            $nuevo = $log->modified_data['status'];

            $paso = match (true) {
                $nuevo === InventoryTransfer::STATUS_PENDING_AUTHORIZATION => [1, 'Enviado a autorización', $log->user?->username],
                $nuevo === InventoryTransfer::STATUS_DRAFT && $anterior === InventoryTransfer::STATUS_PENDING_AUTHORIZATION => [
                    1,
                    'Rechazado: volvió a borrador',
                    collect([$log->user?->username, $log->modified_data['status_reason'] ?? null])->filter()->join(' · '),
                ],
                $nuevo === InventoryTransfer::STATUS_IN_TRANSIT && $anterior !== InventoryTransfer::STATUS_PREPARING => [6, 'Sigue en tránsito', $log->user?->username],
                $nuevo === InventoryTransfer::STATUS_CANCELLED => [9, 'Traslado cancelado', $log->user?->username],
                default => null,
            };

            if ($paso) {
                $pasos->push((object) ['orden' => $paso[0], 'fecha' => $log->created_at, 'titulo' => $paso[1], 'detalle' => $paso[2]]);
            }
        }

        if ($transfer->authorized_at) {
            $pasos->push((object) ['orden' => 2, 'fecha' => $transfer->authorized_at, 'titulo' => 'Autorizado', 'detalle' => $transfer->authorizedBy?->username]);
        }

        if ($transfer->prepared_at) {
            $pasos->push((object) [
                'orden' => 3,
                'fecha' => $transfer->prepared_at,
                'titulo' => 'Despacho en preparación',
                'detalle' => $quien([InventoryTransfer::STATUS_AUTHORIZED], InventoryTransfer::STATUS_PREPARING),
            ]);
        }

        if ($transfer->dispatched_at) {
            $pasos->push((object) [
                'orden' => 4,
                'fecha' => $transfer->dispatched_at,
                'titulo' => 'Despachado: en tránsito',
                'detalle' => collect([
                    $quien([InventoryTransfer::STATUS_PREPARING], InventoryTransfer::STATUS_IN_TRANSIT),
                    $transfer->vehicle?->label(),
                    $transfer->vehicleLocation?->code,
                    $transfer->driver?->fullName(),
                ])->filter()->join(' · '),
            ]);
        }

        InventoryMovement::queryAllBranches()
            ->with('user')
            ->where('reference_type', InventoryMovement::REFERENCE_TRANSFER)
            ->where('reference_id', $transfer->id_inventory_transfer)
            ->where('movement_type', InventoryMovement::TYPE_TRANSFER_IN)
            ->orderBy('id_inventory_movement')
            ->get()
            ->groupBy(fn ($m) => $m->movement_date->format('Y-m-d H:i:s').'|'.$m->id_user)
            ->each(fn ($grupo) => $pasos->push((object) [
                'orden' => 5,
                'fecha' => $grupo->first()->movement_date,
                'titulo' => 'Recepción de '.self::unidades((float) $grupo->sum('quantity_in')),
                'detalle' => $grupo->first()->user?->username,
            ]));

        $discrepancias = $transfer->details->flatMap->discrepancies;

        $discrepancias->groupBy(fn ($d) => $d->created_at->format('Y-m-d H:i'))
            ->each(fn ($grupo) => $pasos->push((object) [
                'orden' => 7,
                'fecha' => $grupo->first()->created_at,
                'titulo' => 'Discrepancias registradas',
                'detalle' => $grupo->groupBy('discrepancy_type')
                    ->map(fn ($tipo, $clave) => InventoryTransferDiscrepancy::TYPE_LABELS[$clave].': '.rtrim(rtrim(number_format((float) $tipo->sum('quantity'), 4, '.', ''), '0'), '.'))
                    ->join(' · '),
            ]));

        if ($regularizada = $discrepancias->whereNotNull('regularized_at')->max('regularized_at')) {
            $pasos->push((object) [
                'orden' => 8,
                'fecha' => $regularizada,
                'titulo' => 'Existencias regularizadas',
                'detalle' => $quien([InventoryTransfer::STATUS_WITH_DISCREPANCY], InventoryTransfer::STATUS_RECEIVED),
            ]);
        }

        if ($transfer->received_at) {
            $pasos->push((object) ['orden' => 9, 'fecha' => $transfer->received_at, 'titulo' => 'Traslado finalizado: recibido', 'detalle' => null]);
        }

        // Orden estable: los pasos del mismo segundo y orden quedan como se agregaron
        return $pasos
            ->sort(fn ($a, $b) => [$a->fecha->getTimestamp(), $a->orden] <=> [$b->fecha->getTimestamp(), $b->orden])
            ->values();
    }

    private function accion(\Closure $accion, string $mensaje)
    {
        try {
            $accion();
        } catch (\InvalidArgumentException $e) {
            return back()->with('error', $e->getMessage());
        }

        return back()->with('success', $mensaje);
    }

    /**
     * Reglas del formulario. Las líneas sin cantidad no se envían al servicio.
     */
    private function validar(Request $request, ?int $origenId, ?int $destinoId, ?Warehouse $origen, ?InventoryTransfer $transfer = null): array
    {
        // Conservan su clave (la fila del formulario), para mostrar cada error en su fila
        $request->merge([
            'details' => array_filter(
                (array) $request->input('details', []),
                fn ($linea) => is_array($linea) && (float) ($linea['quantity_requested'] ?? 0) > 0
            ),
        ]);

        return $request->validate([
            'id_source_warehouse' => ['required', Rule::in($this->bodegasOrigen()->pluck('id_warehouse')->push($origenId)->filter()->all())],
            'id_destination_warehouse' => ['required', 'different:id_source_warehouse', Rule::in($this->bodegasDestino($origen)->pluck('id_warehouse')->all())],
            'id_vehicle' => ['nullable', 'integer', Rule::in($this->vehiculos($transfer)->pluck('id_vehicle')->all())],
            'id_driver_employee' => ['nullable', 'integer', Rule::in($this->motoristas($transfer)->pluck('id_employee')->all())],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id_inventory_lot' => ['required', 'integer'],
            'details.*.id_source_inventory_location' => ['required', 'integer', Rule::in($origenId ? $this->ubicacionesDeBodega($origenId, soloActivas: false)->pluck('id_inventory_location')->all() : [])],
            'details.*.quantity_requested' => ['required', 'numeric', 'min:0.0001'],
            'details.*.id_destination_inventory_location' => ['nullable', 'integer', Rule::in($destinoId ? $this->ubicacionesDeBodega($destinoId)->pluck('id_inventory_location')->all() : [])],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
        ], self::MENSAJES);
    }

    /**
     * Existencia de la bodega origen por lote y ubicación, ubicaciones de la destino, vehículos
     * y motoristas, para el formulario.
     */
    private function datosDeLineas(Warehouse $origen, Warehouse $destino, ?InventoryTransfer $transfer = null): array
    {
        // Solo lo que está en ubicaciones de la bodega: lo que va en un vehículo no se traslada
        $existencias = InventoryLocationDetail::queryAllBranches()
            ->whereIn('id_inventory_location', InventoryLocation::query()
                ->where('location_type', InventoryLocation::TYPE_LOCATION)
                ->inWarehouses($origen->id_warehouse)
                ->select('id_inventory_location'))
            ->where('quantity', '>', 0)
            ->with(['lot.product.purchaseUnit', 'location'])
            ->get()
            ->sortBy(fn (InventoryLocationDetail $fila) => [$fila->lot->id_product, $fila->id_inventory_lot, $fila->id_inventory_location])
            ->values();

        return [
            'existencias' => $existencias,
            'ubicacionesDestino' => $this->ubicacionesDeBodega($destino->id_warehouse),
            'vehiculos' => $this->vehiculos($transfer),
            'motoristas' => $this->motoristas($transfer),
        ];
    }

    /**
     * Ubicaciones de inventario de las ubicaciones de la bodega (no de vehículos), con su código.
     */
    private function ubicacionesDeBodega(int $warehouseId, bool $soloActivas = true): Collection
    {
        return InventoryLocation::query()
            ->with('location')
            ->where('location_type', InventoryLocation::TYPE_LOCATION)
            ->whereIn('id_location', Location::queryAllBranches()->where('id_warehouse', $warehouseId)->select('id_location'))
            ->when($soloActivas, fn ($q) => $q->where('is_active', true))
            ->get()
            ->sortBy(fn (InventoryLocation $ubicacion) => $ubicacion->location->code)
            ->values();
    }

    /**
     * Vehículos activos (y el que ya tiene el traslado).
     */
    private function vehiculos(?InventoryTransfer $transfer = null): Collection
    {
        return Vehicle::query()
            ->where(fn ($q) => $q->where('is_active', true)->when($transfer?->id_vehicle, fn ($w, $id) => $w->orWhere('id_vehicle', $id)))
            ->orderBy('license_plate')
            ->get();
    }

    /**
     * Empleados activos visibles para el usuario (y el motorista que ya tiene el traslado).
     */
    private function motoristas(?InventoryTransfer $transfer = null): Collection
    {
        return Employee::query()
            ->with('branch')
            ->where(fn ($q) => $q->where('is_active', true)->when($transfer?->id_driver_employee, fn ($w, $id) => $w->orWhere('id_employee', $id)))
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * Bodegas de las que puede salir un traslado: las de la sucursal del usuario, o las de toda
     * su empresa para el departamento de compras.
     */
    private function bodegasOrigen(): Collection
    {
        $query = BranchAccess::isPurchasingDepartment()
            ? Warehouse::queryAllBranches()->whereIn('id_branch', BranchAccess::companyBranchIds())
            : Warehouse::query();

        return $query->where('is_active', true)->orderBy('name')->get();
    }

    /**
     * Bodegas a las que puede llegar: las activas de la empresa de la bodega origen (de otras
     * sucursales también), salvo la misma.
     */
    private function bodegasDestino(?Warehouse $origen): Collection
    {
        if (! $origen) {
            return collect();
        }

        return Warehouse::queryAllBranches()
            ->with('branch')
            ->where('is_active', true)
            ->whereKeyNot($origen->id_warehouse)
            ->whereIn('id_branch', $this->sucursalesDeLaEmpresa($origen))
            ->orderBy('name')
            ->get();
    }

    private function sucursalesDeLaEmpresa(Warehouse $bodega): Builder
    {
        $empresa = Branch::queryAllBranches()->whereKey($bodega->id_branch)->value('id_company');

        return Branch::queryAllBranches()->where('id_company', $empresa)->select('id_branch');
    }
}
