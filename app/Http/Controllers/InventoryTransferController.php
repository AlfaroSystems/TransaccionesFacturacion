<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\InventoryMovement;
use App\Models\InventoryTransfer;
use App\Models\Location;
use App\Models\Scopes\BranchScope;
use App\Models\Warehouse;
use App\Services\InventoryTransferService;
use App\Support\BranchAccess;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

/**
 * Traslados de lotes entre bodegas: Casa Matriz reparte a las sucursales lo que entró con las
 * compras. La lógica (existencias, despacho, recepción y merma) está en InventoryTransferService.
 */
class InventoryTransferController extends Controller
{
    private const MENSAJES = [
        'details.required' => 'Indique la cantidad a trasladar de al menos un lote.',
        'id_destination_warehouse.different' => 'La bodega destino debe ser distinta de la de origen.',
        'id_destination_warehouse.in' => 'La bodega destino debe ser de la misma empresa que la de origen.',
        'id_source_warehouse.in' => 'Elija una bodega de origen de su sucursal.',
        'details.*.id_source_location.exists' => 'La ubicación de origen debe ser de la bodega origen.',
        'details.*.id_destination_location.exists' => 'La ubicación destino debe ser de la bodega destino.',
    ];

    public function __construct(private readonly InventoryTransferService $service) {}

    public function index(Request $request)
    {
        Gate::authorize('inventory.ver');

        $status = array_key_exists((string) $request->input('status'), InventoryTransfer::STATUS_LABELS) ? $request->input('status') : null;
        $search = trim((string) $request->input('search'));

        $transfers = InventoryTransfer::query()
            ->with(['sourceWarehouse', 'destinationWarehouse', 'user'])
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

        $transfer->load([
            'sourceWarehouse.branch' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
            'destinationWarehouse.branch' => fn ($q) => $q->withoutGlobalScope(BranchScope::class),
            'vehicleLocation.warehouse', 'user',
            'details.lot', 'details.product.purchaseUnit', 'details.sourceLocation', 'details.destinationLocation',
        ]);

        $ubicacionesDestino = Location::queryAllBranches()
            ->where('id_warehouse', $transfer->id_destination_warehouse)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return view('inventory_transfers.show', compact('transfer', 'ubicacionesDestino'));
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
            ...$this->datosDeLineas($transfer->sourceWarehouse, $transfer->destinationWarehouse),
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
        $validated = $this->validar($request, $transfer->id_source_warehouse, $transfer->id_destination_warehouse, $transfer->sourceWarehouse()->first());

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

    public function dispatchTransfer(InventoryTransfer $transfer)
    {
        Gate::authorize('inventory.trasladar');

        return $this->accion(fn () => $this->service->despachar($transfer), 'Traslado despachado: su mercadería salió de la bodega origen.');
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
            'received.*.id_destination_location' => ['nullable', 'integer', Rule::exists('locations', 'id_location')->where('id_warehouse', $transfer->id_destination_warehouse)],
        ], ['received.*.id_destination_location.exists' => 'La ubicación debe ser de la bodega destino.']);

        // Sin cantidad, se recibe lo despachado (0 es una cantidad: no llegó nada)
        $recibido = collect($validated['received'] ?? [])
            ->map(fn ($linea) => array_filter(
                ['quantity_received' => $linea['quantity_received'] ?? null],
                fn ($valor) => $valor !== null
            ) + ['id_destination_location' => ($linea['id_destination_location'] ?? null) ?: null])
            ->all();

        return $this->accion(fn () => $this->service->recibir($transfer, $recibido), 'Traslado recibido: su mercadería ya está en la bodega destino.');
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
    private function validar(Request $request, ?int $origenId, ?int $destinoId, ?Warehouse $origen): array
    {
        // Conservan su clave (la fila del formulario), para mostrar cada error en su fila
        $request->merge([
            'details' => array_filter(
                (array) $request->input('details', []),
                fn ($linea) => is_array($linea) && (float) ($linea['quantity_dispatched'] ?? 0) > 0
            ),
        ]);

        return $request->validate([
            'id_source_warehouse' => ['required', Rule::in($this->bodegasOrigen()->pluck('id_warehouse')->push($origenId)->filter()->all())],
            'id_destination_warehouse' => ['required', 'different:id_source_warehouse', Rule::in($this->bodegasDestino($origen)->pluck('id_warehouse')->all())],
            'id_vehicle_location' => ['nullable', Rule::in($this->ubicacionesDeVehiculo($origen)->pluck('id_location')->all())],
            'transfer_date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'details' => ['required', 'array', 'min:1'],
            'details.*.id_inventory_lot' => ['required', 'integer'],
            'details.*.id_source_location' => ['nullable', 'integer', Rule::exists('locations', 'id_location')->where('id_warehouse', $origenId ?? 0)],
            'details.*.quantity_dispatched' => ['required', 'numeric', 'min:0.0001'],
            'details.*.id_destination_location' => ['nullable', 'integer', Rule::exists('locations', 'id_location')->where('id_warehouse', $destinoId ?? 0)],
            'details.*.notes' => ['nullable', 'string', 'max:500'],
        ], self::MENSAJES);
    }

    /**
     * Existencia de la bodega origen por lote y ubicación, ubicaciones de la destino y de
     * vehículo, para el formulario.
     */
    private function datosDeLineas(Warehouse $origen, Warehouse $destino): array
    {
        $existencias = InventoryMovement::queryAllBranches()
            ->selectRaw('id_inventory_lot, id_product, id_location, SUM(quantity_in - quantity_out) AS stock')
            ->where('id_warehouse', $origen->id_warehouse)
            ->groupBy('id_inventory_lot', 'id_product', 'id_location')
            ->havingRaw('SUM(quantity_in - quantity_out) > 0')
            ->with(['lot', 'product.purchaseUnit', 'location'])
            ->orderBy('id_product')
            ->orderBy('id_inventory_lot')
            ->get();

        $ubicacionesDestino = Location::queryAllBranches()
            ->where('id_warehouse', $destino->id_warehouse)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return [
            'existencias' => $existencias,
            'ubicacionesDestino' => $ubicacionesDestino,
            'ubicacionesVehiculo' => $this->ubicacionesDeVehiculo($origen),
        ];
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

    /**
     * Ubicación del vehículo o de tránsito (opcional): cualquier ubicación activa de la empresa.
     */
    private function ubicacionesDeVehiculo(?Warehouse $origen): Collection
    {
        if (! $origen) {
            return collect();
        }

        return Location::queryAllBranches()
            ->with('warehouse')
            ->where('is_active', true)
            ->whereIn('id_warehouse', Warehouse::queryAllBranches()->whereIn('id_branch', $this->sucursalesDeLaEmpresa($origen))->select('id_warehouse'))
            ->orderBy('id_warehouse')
            ->orderBy('code')
            ->get();
    }

    private function sucursalesDeLaEmpresa(Warehouse $bodega): Builder
    {
        $empresa = Branch::queryAllBranches()->whereKey($bodega->id_branch)->value('id_company');

        return Branch::queryAllBranches()->where('id_company', $empresa)->select('id_branch');
    }
}
