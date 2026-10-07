<?php

namespace App\Http\Controllers;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SubCategoryController extends Controller
{
    /**
     * Mostrar todas las subcategorías.
     * También permite filtrar por categoría.
     */
    public function index(Request $request)
    {
        Gate::authorize('subcategories.ver');

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
        Gate::authorize('subcategories.crear');

        return redirect()->route('subcategories.index');
    }

    /**
     * Guardar una nueva subcategoría.
     */
    public function store(Request $request)
    {
        Gate::authorize('subcategories.crear');

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
            ->route('subcategories.index')
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
        Gate::authorize('subcategories.ver');

        return redirect()->route('subcategories.index');
    }

    /**
     * Mostrar formulario para editar.
     */
    public function edit(SubCategory $subCategory)
    {
        Gate::authorize('subcategories.editar');

        return redirect()->route('subcategories.index');
    }

    /**
     * Actualizar una subcategoría.
     */
    public function update(
        Request $request,
        SubCategory $subCategory
    ) {
        Gate::authorize('subcategories.editar');

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
            ->route('subcategories.index')
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
        Gate::authorize('subcategories.eliminar');

        $newStatus = !$subCategory->is_active;

        $subCategory->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'Subcategoría reactivada correctamente.' : 'Subcategoría inactivada correctamente.';

        return redirect()
            ->route('subcategories.index')
            ->with('success', $message);
    }
}