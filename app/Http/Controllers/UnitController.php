<?php

namespace App\Http\Controllers;
use App\Models\Unit;
use App\Http\Requests\StoreUnitRequest;
use App\Http\Requests\UpdateUnitRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class UnitController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Gate::authorize('units.ver');

        $units = Unit::query()
            ->when($request->filled('search'), function ($query) use ($request) {
                $search = $request->search;
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'ILIKE', "%{$search}%")
                        ->orWhere('type', 'ILIKE', "%{$search}%");
                });
            })
            ->when($request->filled('status'), function ($query) use ($request) {
                if ($request->status === 'active') {
                    $query->where('is_active', true);
                }

                if ($request->status === 'inactive') {
                    $query->where('is_active', false);
                }
            })
            ->orderBy('name', 'asc')
            ->paginate(10)
            ->withQueryString();

        return view('units.index', compact('units'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * El formulario se manejará mediante un modal
     * dentro de units.index.
     */
    public function create()
    {
        Gate::authorize('units.crear');

        return redirect()->route('units.index');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreUnitRequest $request)
    {
        Gate::authorize('units.crear');

        Unit::create($request->validated());

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidad de medida creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Unit $unit)
    {
        Gate::authorize('units.ver');

        return redirect()->route('units.index');
    }

    public function edit(Unit $unit)
    {
        Gate::authorize('units.editar');

        return redirect()->route('units.index');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateUnitRequest $request, Unit $unit)
    {
        Gate::authorize('units.editar');

        $unit->update($request->validated());

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidad de medida actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     *
     * Realiza una desactivación lógica.
     */
    public function destroy(Unit $unit)
    {
        Gate::authorize('units.desactivar');

        $unit->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('units.index')
            ->with('success', 'Unidad de medida desactivada exitosamente.');
    }
}