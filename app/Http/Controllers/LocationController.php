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
     * Mostrar el mapa esquemático de ubicaciones en bodega.
     */
    public function map()
    {
        Gate::authorize('locations.ver');

        $locations = Location::with('warehouse')->where('is_active', true)->get();
        $warehouses = class_exists(Warehouse::class) ? Warehouse::all() : collect();

        return view('locations.map', compact('locations', 'warehouses'));
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
     * Generación masiva de ubicaciones por rangos.
     */
    public function batchStore(Request $request)
    {
        Gate::authorize('locations.crear');

        $request->validate([
            'warehouse_id' => 'required|exists:warehouses,id',
            'pasillo_hasta' => 'required|string|max:10',
            'rack_hasta' => 'required|integer|min:1',
            'level_hasta' => 'required|integer|min:1',
            'position_hasta' => 'required|integer|min:1',
            'capacity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
        ]);

        $pasilloHasta = strtoupper(trim($request->pasillo_hasta));
        $rackHasta = (int) $request->rack_hasta;
        $levelHasta = (int) $request->level_hasta;
        $positionHasta = (int) $request->position_hasta;
        $capacity = (int) $request->capacity;
        $warehouseId = $request->warehouse_id;
        $notes = $request->notes;

        // Determinar el rango de pasillos (Alfabético A-Z o Numérico 1-N)
        $pasillos = [];
        if (ctype_alpha($pasilloHasta) && strlen($pasilloHasta) === 1) {
            for ($char = ord('A'); $char <= ord($pasilloHasta); $char++) {
                $pasillos[] = chr($char);
            }
        } elseif (is_numeric($pasilloHasta)) {
            $maxPasillo = (int) $pasilloHasta;
            for ($p = 1; $p <= $maxPasillo; $p++) {
                $pasillos[] = (string) $p;
            }
        } else {
            $pasillos[] = $pasilloHasta;
        }

        $createdCount = 0;
        $skippedCount = 0;

        foreach ($pasillos as $pasillo) {
            for ($r = 1; $r <= $rackHasta; $r++) {
                for ($l = 1; $l <= $levelHasta; $l++) {
                    for ($pos = 1; $pos <= $positionHasta; $pos++) {
                        $code = "{$pasillo}-{$r}-{$l}-{$pos}";

                        $location = Location::firstOrCreate(
                            [
                                'warehouse_id' => $warehouseId,
                                'code' => $code,
                            ],
                            [
                                'pasillo' => $pasillo,
                                'rack' => (string) $r,
                                'level' => (string) $l,
                                'position' => (string) $pos,
                                'capacity' => $capacity,
                                'notes' => $notes,
                                'is_active' => true,
                            ]
                        );

                        if ($location->wasRecentlyCreated) {
                            $createdCount++;
                        } else {
                            $skippedCount++;
                        }
                    }
                }
            }
        }

        $msg = "Se generaron exitosamente {$createdCount} ubicaciones masivas.";
        if ($skippedCount > 0) {
            $msg .= " ({$skippedCount} ubicaciones ya existían y se omitieron).";
        }

        return redirect()
            ->route('locations.index')
            ->with('success', $msg);
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

        $location->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('locations.index')
            ->with('success', 'Ubicación inactivada correctamente.');
    }
}