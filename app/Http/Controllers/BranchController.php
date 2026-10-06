<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Company;
use App\Models\Department;
use App\Models\Municipality;
use App\Models\District;
use App\Http\Requests\BranchRequest;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    /**
     * Listar sucursales
     */
    public function index()
    {
        Gate::authorize('branches.ver');

        $branches = Branch::with(['company', 'department', 'municipality', 'district'])->orderBy('id_branch', 'desc')->get();
        $companies = Company::all();
        $departments = Department::orderBy('name')->get();
        $municipalities = Municipality::orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('branches.index', compact('branches', 'companies', 'departments', 'municipalities', 'districts'));
    }

    /**
     * Mostrar formulario para crear
     */
    public function create()
    {
        Gate::authorize('branches.crear');

        // El formulario está en un modal del listado
        return redirect()->route('branches.index');
    }

    /**
     * Guardar sucursal
     */
    public function store(BranchRequest $request)
    {
        Gate::authorize('branches.crear');
        Branch::create($request->validated());
        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal creada correctamente.');
    }

    /**
     * Mostrar detalle
     */
    public function show(Branch $branch)
    {
        Gate::authorize('branches.ver');
        return view('branches.show', compact('branch'));
    }

    /**
     * Mostrar formulario editar
     */
    public function edit(Branch $branch)
    {
        Gate::authorize('branches.editar');

        // El formulario está en un modal del listado
        return redirect()->route('branches.index');
    }

    /**
     * Actualizar sucursal
     */
    public function update(BranchRequest $request, Branch $branch)
    {
        Gate::authorize('branches.editar');
        $branch->update($request->validated());
        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal actualizada correctamente.');
    }

    /**
     * Eliminar sucursal
     */
    public function destroy(Branch $branch)
    {
        Gate::authorize('branches.eliminar');

        $newStatus = !$branch->is_active;

        $branch->update([
            'is_active' => $newStatus,
        ]);

        $message = $newStatus ? 'Sucursal reactivada correctamente.' : 'Sucursal inactivada correctamente.';

        return redirect()
            ->route('branches.index')
            ->with('success', $message);
    }
}