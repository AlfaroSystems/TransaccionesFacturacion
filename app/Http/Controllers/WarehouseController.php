<?php

namespace App\Http\Controllers;

use App\Models\Warehouse;
use App\Models\Branch;
use App\Models\WarehouseCategory;
use Illuminate\Http\Request;

class WarehouseController extends Controller
{


    public function index()
    {

        $warehouses = Warehouse::with([
            'branch',
            'warehouseCategory'
        ])
        ->orderBy('name')
        ->get();

        $branches = Branch::where('is_active', true)->orderBy('name')->get();
        $categories = WarehouseCategory::where('is_active', true)->orderBy('name')->get();

        return view(
            'warehouses.index',
            compact('warehouses', 'branches', 'categories')
        );
    }



    public function create()
    {

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

        $warehouse->delete();


        return redirect()
            ->route('warehouses.index')
            ->with(
                'success',
                'Bodega eliminada'
            );

    }

}