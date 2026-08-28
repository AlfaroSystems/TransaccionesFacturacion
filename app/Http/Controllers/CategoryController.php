<?php

namespace App\Http\Controllers;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    /**
     * Mostrar listado de categorías
     */
    public function index(Request $request)
    {
        Gate::authorize('categories.ver');

        $categories = Category::query()
            ->when($request->search, function ($query) use ($request) {
                $query->where('name', 'like', '%' . $request->search . '%');
            })
            ->get();

        return view('categories.index', compact('categories'));
    }

    /**
     * Mostrar formulario para crear categoría
     */
    public function create()
    {
        Gate::authorize('categories.crear');

        return redirect()->route('categories.index');
    }

    /**
     * Guardar nueva categoría
     */
    public function store(Request $request)
    {
        Gate::authorize('categories.crear');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        Category::create([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active')
                ? $request->boolean('is_active')
                : true,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría creada correctamente');
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Category $category)
    {
        Gate::authorize('categories.editar');

        return redirect()->route('categories.index');
    }

    /**
     * Actualizar categoría
     */
    public function update(Request $request, Category $category)
    {
        Gate::authorize('categories.editar');

        $validated = $request->validate([
            'name' => 'required|string|max:100',
            'description' => 'nullable|string',
        ]);

        $category->update([
            'name' => $validated['name'],
            'description' => $validated['description'] ?? null,
            'is_active' => $request->has('is_active'),
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría actualizada correctamente');
    }

    /**
     * Cambiar estado activo/inactivo
     */
    public function toggleStatus(Category $category)
    {
        Gate::authorize('categories.editar');

        $category->update([
            'is_active' => !$category->is_active,
        ]);

        return redirect()
            ->route('categories.index')
            ->with('success', 'Estado de categoría actualizado');
    }

    /**
     * Eliminar categoría
     */
    public function destroy(Category $category)
    {
        Gate::authorize('categories.eliminar');

        $category->delete();

        return redirect()
            ->route('categories.index')
            ->with('success', 'Categoría eliminada correctamente');
    }
}