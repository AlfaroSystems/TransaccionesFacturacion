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

        $branches = Branch::with(['company', 'department', 'municipality', 'district'])->orderBy('id', 'desc')->get();
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
        $companies = Company::all();
        $departments = Department::orderBy('name')->get();
        $municipalities = Municipality::orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('branches.create', compact('companies', 'departments', 'municipalities', 'districts'));
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
        $companies = Company::all();
        $departments = Department::orderBy('name')->get();
        $municipalities = Municipality::orderBy('name')->get();
        $districts = District::orderBy('name')->get();

        return view('branches.edit', compact('branch', 'companies', 'departments', 'municipalities', 'districts'));
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

        $branch->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('branches.index')
            ->with('success', 'Sucursal inactivada correctamente.');
    }
}