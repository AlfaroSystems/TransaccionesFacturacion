<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Branch;
use App\Models\Company;
use App\Http\Requests\BranchRequest;

class BranchController extends Controller
{
    /**
     * / Listar sucursales
     */
   public function index()
{
    $branches = Branch::with('company')->get();
    $companies = Company::all();

    return view('branches.index', compact('branches', 'companies'));
}

    /**
     *  Mostrar formulario para crear
     */
   public function create()
{
    $companies = Company::all();

    return view('branches.create', compact('companies'));
}

    /**
     * Guardar sucursal
     */
    public function store(BranchRequest $request)
{
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
    return view('branches.show', compact('branch'));
}

    /**
     * Mostrar formulario editar
     */
public function edit(Branch $branch)
{
    $companies = Company::all();

    return view('branches.edit', compact('branch', 'companies'));
}
    /**
     * Actualizar sucursal
     */
   public function update(BranchRequest $request, Branch $branch)
{
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
    $branch->delete();

    return redirect()
        ->route('branches.index')
        ->with('success', 'Sucursal eliminada correctamente.');
}
}
