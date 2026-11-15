<?php

namespace App\Http\Controllers;

use App\Models\InventoryLocation;
use App\Models\InventoryLocationDetail;
use App\Models\InventoryLot;
use App\Models\InventoryMovement;
use App\Models\Location;
use App\Models\Warehouse;
use App\Services\InventoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;
use InvalidArgumentException;

/**
 * Lotes: los que creó el retaceo aplicado esperan su ubicación; se reparten en las ubicaciones
 * de su bodega y al confirmar la colocación entran al inventario. Después se cambian de
 * ubicación con movimientos. La lógica está en InventoryService.
 */
class InventoryLotController extends Controller
{
    private const MENSAJES_REPARTO = [
        'placements.*.id_location.required_with' => 'Elija la ubicación de esta fila.',
        'placements.*.quantity.required_with' => 'Indique cuánto del lote va en esta ubicación.',
    ];

    public function __construct(private readonly InventoryService $service) {}

    /**
     * Lotes por estado; de entrada, los que faltan por ubicar.
     */
    public function index(Request $request)
    {
        Gate::authorize('inventory.ver');

        $status = $request->input('status', InventoryLot::STATUS_PENDING);
        $status = array_key_exists((string) $status, InventoryLot::STATUS_LABELS) ? $status : null;
        $search = trim((string) $request->input('search'));

        $lots = InventoryLot::query()
            ->with(['product', 'warehouseAllBranches', 'purchaseDetail.purchase', 'purchaseDetail.unit'])
            ->withSum('placements', 'quantity')
            ->withCount('labelPrints')
            ->when($status, fn ($q) => $q->where('status', $status))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('lot_code', 'ilike', "%{$search}%")
                ->orWhereHas('product', fn ($p) => $p->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%"))))
            ->orderByDesc('id_inventory_lot')
            ->paginate(20)
            ->withQueryString();

        $conteos = InventoryLot::query()->groupBy('status')->selectRaw('status, COUNT(*) AS total')->pluck('total', 'status');

        return view('inventory.lots', compact('lots', 'status', 'search', 'conteos'));
    }

    public function show(InventoryLot $lot)
    {
        Gate::authorize('inventory.ver');

        $lot->load([
            'product.purchaseUnit', 'warehouseAllBranches', 'purchaseDetail.unit', 'purchaseDetail.purchase',
            'placements.location', 'labelPrints',
        ]);

        // Por ubicar: las ubicaciones activas de su bodega, con lo que ya guarda cada una
        $ubicaciones = collect();
        $ocupado = collect();
        $sugerida = null;

        if ($lot->isPending()) {
            $ubicaciones = Location::queryAllBranches()
                ->where('id_warehouse', $lot->id_warehouse)
                ->where('is_active', true)
                ->orderBy('code')
                ->get();

            $ocupado = $this->ocupacion($ubicaciones->pluck('id_location')->all());

            // Para el primer reparto: la última ubicación donde entró el producto en esa bodega
            $sugerida = InventoryLocation::query()
                ->whereIn('id_inventory_location', InventoryMovement::queryAllBranches()
                    ->where('id_warehouse', $lot->id_warehouse)
                    ->where('id_product', $lot->id_product)
                    ->where('movement_type', InventoryMovement::TYPE_PURCHASE_RECEIPT)
                    ->whereNotNull('id_inventory_location')
                    ->orderByDesc('id_inventory_movement')
                    ->limit(1)
                    ->select('id_inventory_location'))
                ->value('id_location');
        }

        // Disponible: dónde está y a qué ubicaciones de esas bodegas se puede mover
        $existencias = $lot->status === InventoryLot::STATUS_ACTIVE ? $this->service->existenciasDelLote($lot) : collect();
        $destinos = InventoryLocation::query()
            ->with('location')
            ->where('location_type', InventoryLocation::TYPE_LOCATION)
            ->where('is_active', true)
            ->whereIn('id_location', Location::queryAllBranches()->whereIn('id_warehouse', $existencias->pluck('id_warehouse')->filter()->unique())->select('id_location'))
            ->get()
            ->sortBy(fn (InventoryLocation $destino) => $destino->location->code)
            ->groupBy(fn (InventoryLocation $destino) => $destino->location->id_warehouse);

        $movimientos = $lot->movements()
            ->with(['warehouse', 'location', 'inventoryLocation.vehicleLocation.vehicle', 'user'])
            ->orderBy('movement_date')
            ->orderBy('id_inventory_movement')
            ->get();

        if ($lot->status === InventoryLot::STATUS_CANCELLED) {
            session()->now('warning', 'Lote anulado al revertir el retaceo de su compra; sus viñetas ya no valen. Al aplicar de nuevo el retaceo se crea un lote nuevo.');
        }

        return view('inventory.lot', compact('lot', 'ubicaciones', 'ocupado', 'sugerida', 'existencias', 'destinos', 'movimientos'));
    }

    /**
     * Número de lote del proveedor y vencimiento, mientras el lote está por ubicar.
     */
    public function update(Request $request, InventoryLot $lot)
    {
        Gate::authorize('inventory.ubicar');

        $validated = $request->validate([
            'supplier_lot_number' => ['nullable', 'string', 'max:100'],
            'expiration_date' => ['nullable', 'date'],
        ]);

        try {
            $this->service->actualizarDatos($lot, $validated);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Datos del lote guardados.');
    }

    /**
     * Divide el lote por ubicar en varias partes que suman todo el lote.
     */
    public function split(Request $request, InventoryLot $lot)
    {
        Gate::authorize('inventory.ubicar');

        $validated = $request->validate([
            'parts' => ['required', 'array', 'min:2'],
            'parts.*.quantity' => ['required', 'numeric', 'gt:0'],
            'parts.*.supplier_lot_number' => ['nullable', 'string', 'max:100'],
            'parts.*.expiration_date' => ['nullable', 'date'],
        ], [
            'parts.min' => 'Indique al menos dos partes para dividir el lote.',
            'parts.*.quantity.required' => 'Indique la cantidad de cada parte.',
            'parts.*.quantity.gt' => 'La cantidad de cada parte debe ser mayor que cero.',
        ]);

        $impresas = $lot->labelPrints()->exists();

        try {
            $lotes = $this->service->dividirLote($lot, $validated['parts']);
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        $respuesta = redirect()
            ->route('inventory.lots.show', $lot)
            ->with('success', 'Lote dividido en '.$lotes->count().': '.$lotes->pluck('lot_code')->join(', ').'. Ubique cada uno.');

        // Sus viñetas eran del lote completo: las de cada parte se imprimen desde su lote
        if ($impresas) {
            $respuesta->with('warning', 'Las viñetas que ya se imprimieron eran del lote completo: reimprima las de cada parte.');
        }

        return $respuesta;
    }

    /**
     * Guarda el reparto del lote por ubicar y, con ?confirmar, confirma su colocación. Si una
     * ubicación no es válida, vuelve al formulario con el error en su fila.
     */
    public function updatePlacements(Request $request, InventoryLot $lot)
    {
        Gate::authorize('inventory.ubicar');

        $validated = $request->validate([
            'placements' => ['nullable', 'array'],
            'placements.*.id_location' => ['nullable', 'integer', 'required_with:placements.*.quantity'],
            'placements.*.quantity' => ['nullable', 'numeric', 'min:0', 'required_with:placements.*.id_location'],
        ], self::MENSAJES_REPARTO);

        // Las filas vacías no cuentan; las demás conservan su índice para mostrar sus errores
        $lineas = array_filter($validated['placements'] ?? [], fn ($l) => filled($l['id_location'] ?? null));

        try {
            $this->service->guardarReparto($lot, $lineas);

            if ($request->boolean('confirmar')) {
                $this->service->confirmarColocacion($lot);

                return redirect()
                    ->route('inventory.lots.show', $lot)
                    ->with('success', "Lote {$lot->lot_code} colocado: ya está disponible en inventario.");
            }
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Reparto guardado. Confirme la colocación cuando el lote esté en sus ubicaciones.');
    }

    /**
     * Cambio de ubicación de lo que ya está en inventario, dentro de una bodega del usuario.
     */
    public function relocate(Request $request, InventoryLot $lot)
    {
        Gate::authorize('inventory.ubicar');

        $validated = $request->validate([
            'id_warehouse' => ['required', 'integer', Rule::in(Warehouse::query()->pluck('id_warehouse')->all())],
            'id_source_inventory_location' => ['required', 'integer'],
            'id_destination_inventory_location' => ['required', 'integer'],
            'quantity' => ['required', 'numeric', 'min:0.0001'],
        ], [
            'id_warehouse.in' => 'Solo se cambia de ubicación lo que está en una bodega de su sucursal.',
            'id_destination_inventory_location.required' => 'Elija la ubicación a la que se mueve.',
        ]);

        try {
            $this->service->cambiarUbicacion(
                $lot,
                (int) $validated['id_warehouse'],
                (int) $validated['id_source_inventory_location'],
                (int) $validated['id_destination_inventory_location'],
                (float) $validated['quantity'],
            );
        } catch (InvalidArgumentException $e) {
            return back()->withInput()->with('error', $e->getMessage());
        }

        return back()->with('success', 'Cambio de ubicación registrado en el kardex.');
    }

    /**
     * Existencia que ya guarda cada ubicación (de todos los lotes), para mostrar su capacidad.
     *
     * @param  array<int, int>  $locationIds
     */
    private function ocupacion(array $locationIds)
    {
        return InventoryLocationDetail::queryAllBranches()
            ->join('inventory_locations', 'inventory_locations.id_inventory_location', '=', 'inventory_location_details.id_inventory_location')
            ->whereIn('inventory_locations.id_location', $locationIds)
            ->groupBy('inventory_locations.id_location')
            ->selectRaw('inventory_locations.id_location, SUM(inventory_location_details.quantity) AS stock')
            ->pluck('stock', 'id_location')
            ->map(fn ($stock) => (float) $stock);
    }
}
