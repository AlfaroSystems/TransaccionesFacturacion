<?php

namespace App\Http\Controllers;


use App\Models\WarehouseCategory;
use Illuminate\Http\Request;



class WarehouseCategoryController extends Controller
{


    public function index()
    {

        $categories = WarehouseCategory::all();


        return view(
            'warehouse_categories.index',
            compact('categories')
        );

    }





    public function create()
    {

        return view(
            'warehouse_categories.create'
        );

    }





    public function store(Request $request)
    {


        $validated = $request->validate([

            'name'=>'required|string|max:255',

            'description'=>'nullable|string',

        ]);



        WarehouseCategory::create([

            'name'=>$validated['name'],

            'description'=>$validated['description'] ?? null,

            'is_active'=>$request->has('is_active')

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

        return view(
            'warehouse_categories.edit',
            compact('warehouseCategory')
        );

    }






    public function update(Request $request,
                           WarehouseCategory $warehouseCategory)
    {


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


        $warehouseCategory->delete();


        return redirect()
        ->route('warehouse_categories.index')
        ->with(
            'success',
            'Categoría eliminada'
        );


    }
}