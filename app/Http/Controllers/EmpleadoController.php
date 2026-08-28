<?php

namespace App\Http\Controllers;
use App\Models\Empleado;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class EmpleadoController extends Controller
{
    /**
     * Mostrar listado de empleados
     */
    public function index()
    {
        Gate::authorize('empleados.ver');

        $empleados = Empleado::orderBy('id', 'desc')->get();

        return view('empleados.index', compact('empleados'));
    }

    /**
     * Mostrar formulario de creación
     */
    public function create()
    {
        Gate::authorize('empleados.crear');

        return view('empleados.create');
    }

    /**
     * Guardar empleado
     */
    public function store(Request $request)
    {
        Gate::authorize('empleados.crear');

        $request->validate([
            'nombre_completo' => 'required|max:150',
            'correo' => 'required|email|unique:empleados',
            'telefono' => 'required|max:20',
            'dui' => 'required|max:10|unique:empleados',
        ]);

        Empleado::create([
            'nombre_completo' => $request->nombre_completo,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'dui' => $request->dui,
        ]);

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Empleado registrado correctamente.');
    }

    /**
     * Mostrar un empleado
     */
    public function show(Empleado $empleado)
    {
        Gate::authorize('empleados.ver');

        return view('empleados.show', compact('empleado'));
    }

    /**
     * Mostrar formulario de edición
     */
    public function edit(Empleado $empleado)
    {
        Gate::authorize('empleados.editar');

        return view('empleados.edit', compact('empleado'));
    }

    /**
     * Actualizar empleado
     */
    public function update(Request $request, Empleado $empleado)
    {
        Gate::authorize('empleados.editar');

        $request->validate([
            'nombre_completo' => 'required|max:150',
            'correo' => 'required|email|unique:empleados,correo,' . $empleado->id,
            'telefono' => 'required|max:20',
            'dui' => 'required|max:10|unique:empleados,dui,' . $empleado->id,
        ]);

        $empleado->update([
            'nombre_completo' => $request->nombre_completo,
            'correo' => $request->correo,
            'telefono' => $request->telefono,
            'dui' => $request->dui,
        ]);

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Empleado actualizado correctamente.');
    }

    /**
     * Eliminar empleado
     */
    public function destroy(Empleado $empleado)
    {
        Gate::authorize('empleados.eliminar');

        $empleado->update([
            'is_active' => false,
        ]);

        return redirect()
            ->route('empleados.index')
            ->with('success', 'Empleado inactivado correctamente.');
    }
}