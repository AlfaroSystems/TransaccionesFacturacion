<?php

namespace App\Http\Controllers;
use App\Models\Location;
use App\Models\Warehouse;
use App\Rules\Accessible;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Validation\Rule;

class LocationController extends Controller
{
    /** Máximo de ubicaciones por generación masiva */
    private const BATCH_LIMIT = 2000;

    /**
     * Mostrar listado de ubicaciones.
     */
    public function index()
    {
        Gate::authorize('locations.ver');

        $locations = Location::with('warehouse')->orderBy('id_location', 'desc')->get();
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

        // El formulario está en un modal del listado
        return redirect()->route('locations.index');
    }

    /**
     * Guardar ubicación.
     */
    public function store(Request $request)
    {
        Gate::authorize('locations.crear');

        $request->validate([
            'id_warehouse' => ['required', new Accessible(Warehouse::class)],
            // El código se repite entre bodegas (A-1-1-1 en cada una), pero no dentro de una
            'code' => ['required', 'string', 'max:255', Rule::unique('locations', 'code')->where('id_warehouse', $request->input('id_warehouse'))],
            'aisle' => 'nullable|string|max:255',
            'rack' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        Location::create([
            'id_warehouse' => $request->id_warehouse,
            'code' => $request->code,
            'aisle' => $request->aisle,
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
            'id_warehouse' => ['required', new Accessible(Warehouse::class)],
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
        $warehouseId = $request->id_warehouse;
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

        // Tope por generación: un dato mal escrito (p. ej. 500 racks) crearía miles de
        // ubicaciones y la petición tardaría demasiado
        $total = count($pasillos) * $rackHasta * $levelHasta * $positionHasta;

        if ($total > self::BATCH_LIMIT) {
            return redirect()
                ->route('locations.index')
                ->with('error', "La generación produciría {$total} ubicaciones; el máximo por vez es " . self::BATCH_LIMIT . '.');
        }

        $createdCount = 0;
        $skippedCount = 0;

        // Todo o nada: si algo falla, no quedan ubicaciones generadas a medias
        DB::transaction(function () use ($pasillos, $rackHasta, $levelHasta, $positionHasta, $warehouseId, $capacity, $notes, &$createdCount, &$skippedCount) {
            // Códigos que ya existen en la bodega, en una sola consulta
            $existing = Location::where('id_warehouse', $warehouseId)->pluck('code')->flip();

            foreach ($pasillos as $pasillo) {
                for ($r = 1; $r <= $rackHasta; $r++) {
                    for ($l = 1; $l <= $levelHasta; $l++) {
                        for ($pos = 1; $pos <= $positionHasta; $pos++) {
                            $code = "{$pasillo}-{$r}-{$l}-{$pos}";

                            if ($existing->has($code)) {
                                $skippedCount++;

                                continue;
                            }

                            Location::create([
                                'id_warehouse' => $warehouseId,
                                'code' => $code,
                                'aisle' => $pasillo,
                                'rack' => (string) $r,
                                'level' => (string) $l,
                                'position' => (string) $pos,
                                'capacity' => $capacity,
                                'notes' => $notes,
                                'is_active' => true,
                            ]);
                            $createdCount++;
                        }
                    }
                }
            }
        });

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

        // El formulario está en un modal del listado
        return redirect()->route('locations.index');
    }

    /**
     * Actualizar ubicación.
     */
    public function update(Request $request, Location $location)
    {
        Gate::authorize('locations.editar');

        $request->validate([
            'id_warehouse' => ['required', new Accessible(Warehouse::class)],
            'code' => ['required', 'string', 'max:255', Rule::unique('locations', 'code')->where('id_warehouse', $request->input('id_warehouse'))->ignore($location)],
            'aisle' => 'nullable|string|max:255',
            'rack' => 'nullable|string|max:255',
            'level' => 'nullable|string|max:255',
            'position' => 'nullable|string|max:255',
            'capacity' => 'required|integer|min:0',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
        ]);

        $location->update([
            'id_warehouse' => $request->id_warehouse,
            'code' => $request->code,
            'aisle' => $request->aisle,
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

        $newStatus = !$location->is_active;

        $location->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'Ubicación reactivada correctamente.' : 'Ubicación inactivada correctamente.';

        return redirect()
            ->route('locations.index')
            ->with('success', $message);
    }
}