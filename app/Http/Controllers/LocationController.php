<?php

namespace App\Http\Controllers;

use App\Models\Location;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class LocationController extends Controller
{
    /**
     * Mostrar listado de ubicaciones.
     */
    public function index()
    {
        Gate::authorize('locations.ver');

        $locations = Location::with('warehouse')->orderBy('id', 'desc')->get();
        $warehouses = class_exists(Warehouse::class) ? Warehouse::all() : collect();

        return view('locations.index', compact('locations', 'warehouses'));
    }

    /**
     * Mostrar formulario de creación.
     */
    public function create()
    {
        Gate::authorize('locations.crear');

        // Si el modelo Warehouse no existe aún en el sistema, manejamos una colección vacía.
        $warehouses = class_exists(Warehouse::class) ? Warehouse::all() : collect();

        return view('locations.create', compact('warehouses'));
    }

    /**
     * Guardar ubicación.
     */
    public function store(Request $request)
    {
        Gate::authorize('locations.crear');

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:255|unique:locations,code',
            'pasillo' => 'nullable|string|max:255',
            'rack' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Location::create([
            'warehouse_id' => $request->warehouse_id,
            'code' => $request->code,
            'pasillo' => $request->pasillo,
            'rack' => $request->rack,
            'level' => $request->level,
            'position' => $request->position,
            'capacity' => $request->capacity,
            'notes' => $request->notes,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : true,
        ]);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Ubicación registrada correctamente.');
    }

    /**
     * Mostrar una ubicación específica.
     */
    public function show(Location $location)
    {
        Gate::authorize('locations.ver');

        $location->load('warehouse');
        return view('locations.show', compact('location'));
    }

    /**
     * Mostrar formulario de edición.
     */
    public function edit(Location $location)
    {
        Gate::authorize('locations.editar');

        $warehouses = class_exists(Warehouse::class) ? Warehouse::all() : collect();
        return view('locations.edit', compact('location', 'warehouses'));
    }

    /**
     * Actualizar ubicación.
     */
    public function update(Request $request, Location $location)
    {
        Gate::authorize('locations.editar');

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'code' => 'required|string|max:255|unique:locations,code,' . $location->id,
            'pasillo' => 'nullable|string|max:255',
            'rack' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update([
            'warehouse_id' => $request->warehouse_id,
            'code' => $request->code,
            'pasillo' => $request->pasillo,
            'rack' => $request->rack,
            'level' => $request->level,
            'position' => $request->position,
            'capacity' => $request->capacity,
            'notes' => $request->notes,
            'is_active' => $request->has('is_active') ? $request->boolean('is_active') : false,
        ]);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Ubicación actualizada correctamente.');
    }

    /**
     * Eliminar ubicación.
     */
    public function destroy(Location $location)
    {
        Gate::authorize('locations.eliminar');

        $location->delete();

        return redirect()
            ->route('locations.index')
            ->with('success', 'Ubicación eliminada correctamente.');
    }
}
