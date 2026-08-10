<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Company;
use App\Http\Requests\BranchRequest;
use Illuminate\Support\Facades\Gate;

class BranchController extends Controller
{
    /**
     * / Listar sucursales
     */
    public function index()
    {
        Gate::authorize('branches.ver');

        $branches = Branch::with(['company', 'department', 'municipality', 'district'])->orderBy('id', 'desc')->get();
        $companies = Company::all();
        $departments = \App\Models\Department::orderBy('name')->get();

        return view('branches.index', compact('branches', 'companies', 'departments'));
    }

    /**
     *  Mostrar formulario para crear
     */
    public function create()
    {
        Gate::authorize('branches.crear');
        $companies = Company::all();
        return view('branches.create', compact('companies'));
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
        return view('branches.edit', compact('branch', 'companies'));
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
        $branch->delete();
        return redirect()
        ->route('branches.index')
        ->with('success', 'Sucursal eliminada correctamente.');
}
}
