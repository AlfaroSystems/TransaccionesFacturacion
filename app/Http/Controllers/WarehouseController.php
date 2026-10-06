<?php

namespace App\Http\Controllers;
use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\WarehouseCategory;
use App\Rules\Accessible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\ValidationException;

class WarehouseController extends Controller
{
    public function index()
    {
        Gate::authorize('warehouses.ver');

        // Warehouse y Branch se filtran por la sucursal del usuario (BranchScope)
        $warehouses = Warehouse::with(['branch', 'warehouseCategory'])->get();
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

        $branches = Branch::where('is_active',true)
            ->orderBy('name')
            ->get();

        $categories = WarehouseCategory::where('is_active',true)
            ->orderBy('name')
            ->get();

        return view(
            'warehouses.create',
            compact(
                'branches',
                'categories'
            )
        );
    }

    public function store(Request $request)
    {
        Gate::authorize('warehouses.crear');

        $validated = $request->validate([
            'branch_id'=>['required', new Accessible(Branch::class)],
            'warehouse_category_id'
            =>'required|exists:warehouse_categories,id',
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

        $branches = Branch::where('is_active',true)
            ->orderBy('name')
            ->get();

        $categories = WarehouseCategory::where('is_active',true)
            ->orderBy('name')
            ->get();

        return view(
            'warehouses.edit',
            compact(
                'warehouse',
                'branches',
                'categories'
            )
        );
    }

    public function update(
        Request $request,
        Warehouse $warehouse
    )
    {
        Gate::authorize('warehouses.editar');
        $validated=$request->validate([
            'branch_id'=>['required', new Accessible(Branch::class)],
            'warehouse_category_id'
            =>'required|exists:warehouse_categories,id',
            'name'
            =>'required|string|max:100',
            'description'
            =>'nullable|string',
            'is_active'
            =>'boolean'
        ]);

        // Una bodega con documentos no puede pasar a otra sucursal: los documentos
        // quedarían apuntando a una bodega ajena a su sucursal
        if ((int) $validated['branch_id'] !== (int) $warehouse->branch_id && $this->hasDocuments($warehouse)) {
            throw ValidationException::withMessages([
                'branch_id' => 'No se puede cambiar la sucursal de una bodega que ya tiene solicitudes, órdenes o compras registradas.',
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
            ->contains(fn ($table) => DB::table($table)->where('id_warehouse', $warehouse->id)->exists());
    }
}