<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\WarehouseCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class WarehouseController extends Controller
{


    public function index()
   {
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

            'branch_id'=>'required|exists:branches,id',

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

            'branch_id'=>'required|exists:branches,id',

            'warehouse_category_id'
            =>'required|exists:warehouse_categories,id',

            'name'
            =>'required|string|max:100',

            'description'
            =>'nullable|string',

            'is_active'
            =>'boolean'

        ]);



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

        $warehouse->delete();


        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Bodega eliminada'
            );

    }

}