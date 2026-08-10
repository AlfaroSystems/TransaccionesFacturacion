<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;

class SubCategoryController extends Controller
{
    /**
     * Mostrar todas las subcategorías.
     * También permite filtrar por categoría.
     */
    public function index(Request $request)
    {
        $query = SubCategory::with('category');

        // Filtro por categoría
        if ($request->filled('id_category')) {
            $query->where('id_category', $request->id_category);
        }

        $subCategories = $query
            ->orderBy('name')
            ->get();

        // Solo categorías activas para el filtro
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('subcategories.index', compact(
            'subCategories',
            'categories'
        ));
    }


    /**
     * Mostrar formulario para crear una subcategoría.
     */
    public function create()
    {
        // Solo categorías activas
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('sub_categories.create', compact('categories'));
    }


    /**
     * Guardar una nueva subcategoría.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'id_category' => [
                'required',
                'exists:categories,id_category',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
         * Verificar que la categoría exista
         * y además esté activa.
         */
        $category = Category::where(
                'id_category',
                $validated['id_category']
            )
            ->where('is_active', true)
            ->first();

        if (!$category) {

            return back()
                ->withInput()
                ->withErrors([
                    'id_category' =>
                        'La categoría seleccionada no existe o está inactiva.'
                ]);
        }


        // Checkbox activo
        $validated['is_active'] = $request->boolean('is_active');


        SubCategory::create($validated);


        return redirect()
            ->route('sub-categories.index')
            ->with(
                'success',
                'Subcategoría creada correctamente.'
            );
    }


    /**
     * Mostrar una subcategoría.
     */
    public function show(SubCategory $subCategory)
    {
        $subCategory->load('category');

        return view(
            'sub_categories.show',
            compact('subCategory')
        );
    }


    /**
     * Mostrar formulario para editar.
     */
    public function edit(SubCategory $subCategory)
    {
        // Solo categorías activas
        $categories = Category::where('is_active', true)
            ->orderBy('name')
            ->get();

        return view(
            'sub_categories.edit',
            compact(
                'subCategory',
                'categories'
            )
        );
    }


    /**
     * Actualizar una subcategoría.
     */
    public function update(
        Request $request,
        SubCategory $subCategory
    ) {
        $validated = $request->validate([
            'id_category' => [
                'required',
                'exists:categories,id_category',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'description' => [
                'nullable',
                'string',
            ],

            'is_active' => [
                'nullable',
                'boolean',
            ],
        ]);


        /*
         * Verificar que la nueva categoría
         * exista y esté activa.
         */
        $category = Category::where(
                'id_category',
                $validated['id_category']
            )
            ->where('is_active', true)
            ->first();

        if (!$category) {

            return back()
                ->withInput()
                ->withErrors([
                    'id_category' =>
                        'La categoría seleccionada no existe o está inactiva.'
                ]);
        }


        $validated['is_active'] = $request->boolean('is_active');


        $subCategory->update($validated);


        return redirect()
            ->route('sub-categories.index')
            ->with(
                'success',
                'Subcategoría actualizada correctamente.'
            );
    }


    /**
     * Eliminar una subcategoría.
     */
    public function destroy(SubCategory $subCategory)
    {
        $subCategory->delete();

        return redirect()
            ->route('sub-categories.index')
            ->with(
                'success',
                'Subcategoría eliminada correctamente.'
            );
    }


    /**
     * API:
     * Obtener las subcategorías activas
     * pertenecientes a una categoría.
     */
    public function apiByCategory($id)
    {
        // Verificar que la categoría exista
        $category = Category::where(
            'id_category',
            $id
        )->first();

        if (!$category) {

            return response()->json([
                'message' => 'Categoría no encontrada.'
            ], 404);
        }


        /*
         * Obtener únicamente las subcategorías
         * activas de esa categoría.
         */
        $subCategories = SubCategory::where(
                'id_category',
                $id
            )
            ->where('is_active', true)
            ->orderBy('name')
            ->get([
                'id',
                'id_category',
                'name',
                'description',
                'is_active',
            ]);


        return response()->json($subCategories);
    }
}