@extends('layouts.app')

@section('title', 'Empleados')

@section('content')

<div class="max-w-7xl mx-auto">

    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">

        <div>
            <h1 class="text-4xl font-bold text-slate-800">
                Empleados
            </h1>

            <p class="text-gray-500 mt-2">
                Administración de empleados registrados.
            </p>
        </div>

        <a href="{{ route('empleados.create') }}"
           class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition">

            + Nuevo Empleado

        </a>

    </div>


    <!-- Tarjeta -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden">

        <table class="w-full">

            <thead class="bg-gray-100">

                <tr>

                    <th class="px-6 py-4 text-center font-bold text-gray-700">
                        ID
                    </th>

                    <th class="px-6 py-4 text-left font-bold text-gray-700">
                        Nombre Completo
                    </th>

                    <th class="px-6 py-4 text-left font-bold text-gray-700">
                        Correo
                    </th>

                    <th class="px-6 py-4 text-center font-bold text-gray-700">
                        Teléfono
                    </th>

                    <th class="px-6 py-4 text-center font-bold text-gray-700">
                        DUI
                    </th>

                    <th class="px-6 py-4 text-center font-bold text-gray-700">
                        Acciones
                    </th>

                </tr>

            </thead>


            <tbody>

                @forelse($empleados as $empleado)

                <tr class="border-b hover:bg-blue-50 transition">

                    <td class="px-6 py-4 text-center">
                        {{ $empleado->id }}
                    </td>

                    <td class="px-6 py-4 font-semibold text-slate-700">
                        {{ $empleado->nombre_completo }}
                    </td>

                    <td class="px-6 py-4">
                        {{ $empleado->correo }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {{ $empleado->telefono }}
                    </td>

                    <td class="px-6 py-4 text-center">
                        {{ $empleado->dui }}
                    </td>

                    <td class="px-6 py-4">

                        <div class="flex justify-center gap-3">

                            <a href="{{ route('empleados.edit',$empleado->id) }}"
                               class="bg-yellow-500 hover:bg-yellow-600 text-white px-4 py-2 rounded-lg shadow">

                                ✏️ Editar

                            </a>

                            <form action="{{ route('empleados.destroy',$empleado->id) }}"
                                  method="POST">

                                @csrf
                                @method('DELETE')

                                <button
                                    onclick="return confirm('¿Desea eliminar este empleado?')"
                                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg shadow">

                                    🗑 Eliminar

                                </button>

                            </form>

                        </div>

                    </td>

                </tr>

                @empty

                <tr>

                    <td colspan="6" class="py-10 text-center text-gray-500 text-lg">

                        No hay empleados registrados.

                    </td>

                </tr>

                @endforelse

            </tbody>

        </table>

    </div>

</div>

@endsection