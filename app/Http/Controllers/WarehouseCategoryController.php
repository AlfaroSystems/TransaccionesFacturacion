<?php

namespace App\Http\Controllers;
use App\Models\WarehouseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseCategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('warehouse_categories.ver');

        $categories = WarehouseCategory::all();

        return view(
            'warehouse_categories.index',
            compact('categories')
        );
    }

    public function create()
    {
        Gate::authorize('warehouse_categories.crear');

        return redirect()->route('warehouse_categories.index');
    }

    public function store(Request $request)
    {
        Gate::authorize('warehouse_categories.crear');

        $validated = $request->validate([
            'name'=>'required|string|max:255',
            'description'=>'nullable|string',
        ]);

        WarehouseCategory::create([
            'name'=>$validated['name'],
            'description'=>$validated['description'] ?? null,
            'is_active'=>$request->has('is_active') ? $request->boolean('is_active') : true
        ]);

        return redirect()
        ->route('warehouse_categories.index')
        ->with(
            'success',
            'Categoría creada correctamente'
        );
    }

    public function edit(WarehouseCategory $warehouseCategory)
    {
        Gate::authorize('warehouse_categories.editar');

        return redirect()->route('warehouse_categories.index');
    }

    public function update(Request $request, WarehouseCategory $warehouseCategory)
    {
        Gate::authorize('warehouse_categories.editar');

        $validated=$request->validate([
            'name'=>'required|string|max:255',
            'description'=>'nullable|string'
        ]);

        $warehouseCategory->update([
            'name'=>$validated['name'],
            'description'=>$validated['description'] ?? null,
            'is_active'=>$request->has('is_active')
        ]);

        return redirect()
        ->route('warehouse_categories.index')
        ->with(
            'success',
            'Categoría actualizada'
        );
    }

    public function destroy(WarehouseCategory $warehouseCategory)
    {
        Gate::authorize('warehouse_categories.eliminar');


        if ($warehouseCategory->warehouses()->exists()) {

        return redirect()
            ->route('warehouse_categories.index')
            ->with(
                'error',
                'No se puede eliminar la categoría porque tiene almacenes asociados.'
            );
    }

    $warehouseCategory->update([
        'is_active' => false,
    ]);

    return redirect()
        ->route('warehouse_categories.index')
        ->with(
            'success',
            'Categoría inactivada correctamente.'
        );
    }
}