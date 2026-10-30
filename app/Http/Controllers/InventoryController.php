<?php

namespace App\Http\Controllers;

use App\Models\InventoryCostAverage;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Warehouse;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

/**
 * Consulta del inventario: existencias y movimientos. Las entradas las genera
 * InventoryService al recibir compras.
 */
class InventoryController extends Controller
{
    /**
     * Existencias por lote, bodega y ubicación, o por producto con el costo promedio de la
     * empresa (?vista=productos).
     */
    public function index(Request $request)
    {
        Gate::authorize('inventory.ver');

        $vista = $request->input('vista') === 'productos' ? 'productos' : 'lotes';
        $search = trim((string) $request->input('search'));
        $warehouseId = $request->integer('id_warehouse') ?: null;
        $porProducto = fn ($q) => $q->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%");

        $warehouses = $this->bodegasVisibles();
        $showCompany = BranchAccess::isUnrestricted();

        if ($vista === 'productos') {
            $query = InventoryCostAverage::query()
                ->where('quantity_on_hand', '>', 0)
                ->when($search !== '', fn ($q) => $q->whereHas('product', $porProducto));

            $totales = (clone $query)->selectRaw('COUNT(*) AS productos, COALESCE(SUM(inventory_value), 0) AS valor')->first();

            $averages = $query
                ->with(['product.purchaseUnit', 'company'])
                ->orderBy(Product::select('name')->whereColumn('products.id_product', 'inventory_cost_averages.id_product'))
                ->paginate(20)
                ->withQueryString();

            return view('inventory.index', compact('vista', 'search', 'warehouseId', 'warehouses', 'showCompany', 'totales', 'averages'));
        }

        // Existencia de cada lote en cada bodega y ubicación: la suma de sus movimientos
        $query = InventoryMovement::query()
            ->selectRaw('id_inventory_lot, id_product, id_warehouse, id_location, SUM(quantity_in - quantity_out) AS stock')
            ->groupBy('id_inventory_lot', 'id_product', 'id_warehouse', 'id_location')
            ->havingRaw('SUM(quantity_in - quantity_out) > 0')
            ->when($warehouseId, fn ($q) => $q->where('id_warehouse', $warehouseId))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('product', $porProducto)
                ->orWhereHas('lot', fn ($l) => $l->where('lot_code', 'ilike', "%{$search}%"))));

        // El valor de cada fila es su existencia al costo actual de su lote
        $totales = DB::query()
            ->fromSub($query->toBase(), 'existencias')
            ->join('inventory_lots', 'inventory_lots.id_inventory_lot', '=', 'existencias.id_inventory_lot')
            ->selectRaw('COUNT(*) AS filas, COUNT(DISTINCT existencias.id_product) AS productos, COALESCE(SUM(existencias.stock * inventory_lots.unit_cost), 0) AS valor')
            ->first();

        $stock = $query
            ->with(['product', 'lot.purchaseDetail.unit', 'warehouse', 'location'])
            ->orderBy(Product::select('name')->whereColumn('products.id_product', 'inventory_movements.id_product'))
            ->orderBy('id_inventory_lot')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.index', compact('vista', 'search', 'warehouseId', 'warehouses', 'showCompany', 'totales', 'stock'));
    }

    /**
     * Kardex: todos los movimientos, del más reciente al más antiguo.
     */
    public function movements(Request $request)
    {
        Gate::authorize('inventory.ver');

        $search = trim((string) $request->input('search'));
        $warehouseId = $request->integer('id_warehouse') ?: null;
        $type = array_key_exists((string) $request->input('type'), InventoryMovement::TYPE_LABELS) ? $request->input('type') : null;

        $movements = InventoryMovement::query()
            ->with(['product', 'lot', 'warehouse', 'location', 'user'])
            ->when($warehouseId, fn ($q) => $q->where('id_warehouse', $warehouseId))
            ->when($type, fn ($q) => $q->where('movement_type', $type))
            ->when($this->fechaDelFiltro($request, 'date_from'), fn ($q, $desde) => $q->whereDate('movement_date', '>=', $desde))
            ->when($this->fechaDelFiltro($request, 'date_to'), fn ($q, $hasta) => $q->whereDate('movement_date', '<=', $hasta))
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->whereHas('product', fn ($p) => $p->where('name', 'ilike', "%{$search}%")->orWhere('sku', 'ilike', "%{$search}%"))
                ->orWhereHas('lot', fn ($l) => $l->where('lot_code', 'ilike', "%{$search}%"))))
            ->orderByDesc('movement_date')
            ->orderByDesc('id_inventory_movement')
            ->paginate(25)
            ->withQueryString();

        // Código de la compra de cada movimiento (su documento de referencia)
        $purchaseCodes = Purchase::queryAllBranches()
            ->whereIn('id_purchase', $movements->getCollection()
                ->where('reference_type', InventoryMovement::REFERENCE_PURCHASE)
                ->pluck('reference_id'))
            ->pluck('purchase_code', 'id_purchase');

        $warehouses = $this->bodegasVisibles();

        return view('inventory.movements', compact('movements', 'purchaseCodes', 'warehouses', 'search', 'warehouseId', 'type'));
    }

    /**
     * Bodegas para filtrar: las de la sucursal del usuario, o las de toda su empresa para el
     * departamento de compras.
     */
    private function bodegasVisibles()
    {
        $query = BranchAccess::isPurchasingDepartment()
            ? Warehouse::queryAllBranches()->whereIn('id_branch', BranchAccess::companyBranchIds())
            : Warehouse::query();

        return $query->orderBy('name')->get();
    }
}
