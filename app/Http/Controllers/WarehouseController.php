<?php

namespace App\Http\Controllers;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\WarehouseCategory;
use App\Rules\Accessible;
use App\Support\BranchAccess;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index()
    {
        Gate::authorize('warehouses.ver');

        // Warehouse y Branch se filtran por la sucursal del usuario (BranchScope); el
        // departamento de compras ve además (solo lectura) las bodegas de toda su empresa
        $warehouseQuery = BranchAccess::isPurchasingDepartment()
            ? Warehouse::queryAllBranches()->whereIn('id_branch', BranchAccess::companyBranchIds())
            : Warehouse::query();

        $warehouses = $warehouseQuery->with(['branch', 'warehouseCategory'])->get();
        $branches = Branch::where('is_active', true)->get();
        $categories = WarehouseCategory::where('is_active', true)->get();
        
        return view('warehouses.index', compact(
            'warehouses',
            'branches',
            'categories'
        ));
    }

    public function create()
    {
        Gate::authorize('warehouses.crear');

        // El formulario está en un modal del listado
        return redirect()->route('warehouses.index');
    }

    public function store(Request $request)
    {
        Gate::authorize('warehouses.crear');

        $validated = $request->validate([
            'id_branch'=>['required', new Accessible(Branch::class)],
            'id_warehouse_category'
            =>'required|exists:warehouse_category,id_warehouse_category',
            'name'
            =>'required|string|max:100',
            'description'
            =>'nullable|string',
            'is_active'
            =>'boolean'
        ]);

        Warehouse::create($validated);
        
        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Bodega creada correctamente'
            );
    }

    public function edit(Warehouse $warehouse)
    {
        Gate::authorize('warehouses.editar');

        // El formulario está en un modal del listado
        return redirect()->route('warehouses.index');
    }

    public function update(
        Request $request,
        Warehouse $warehouse
    )
    {
        Gate::authorize('warehouses.editar');
        $validated=$request->validate([
            'id_branch'=>['required', new Accessible(Branch::class)],
            'id_warehouse_category'
            =>'required|exists:warehouse_category,id_warehouse_category',
            'name'
            =>'required|string|max:100',
            'description'
            =>'nullable|string',
            'is_active'
            =>'boolean'
        ]);

        // Una bodega con documentos no puede pasar a otra sucursal: los documentos
        // quedarían apuntando a una bodega ajena a su sucursal
        if ((int) $validated['id_branch'] !== (int) $warehouse->id_branch && $this->hasDocuments($warehouse)) {
            throw ValidationException::withMessages([
                'id_branch' => 'No se puede cambiar la sucursal de una bodega que ya tiene solicitudes, órdenes o compras registradas.',
            ]);
        }

        $warehouse->update($validated);

        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Bodega actualizada'
            );
    }

    public function destroy(Warehouse $warehouse)
    {
        Gate::authorize('warehouses.eliminar');

        $newStatus = !$warehouse->is_active;

        $warehouse->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'Bodega reactivada correctamente.' : 'Bodega inactivada correctamente.';

        return redirect()
            ->route('warehouses.index')
            ->with('success', $message);
    }

    /**
     * Indica si algún documento de compras usa la bodega, en cualquier sucursal.
     */
    private function hasDocuments(Warehouse $warehouse): bool
    {
        return collect(['purchase_requests', 'purchase_orders', 'purchases'])
            ->contains(fn ($table) => DB::table($table)->where('id_warehouse', $warehouse->id_warehouse)->exists());
    }
}