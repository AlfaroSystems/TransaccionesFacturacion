@extends('layouts.app')

@section('title', 'Editar Empleado')

@section('content')

<div class="max-w-2xl mx-auto">

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <!-- Encabezado -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-800">
                Editar Empleado
            </h1>

            <p class="text-gray-500 mt-2">
                Modifique la información del empleado.
            </p>
        </div>

        <!-- Mensajes de error -->
        @if ($errors->any())
            <div class="mb-6 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded-lg">
                <strong>Se encontraron errores:</strong>

                <ul class="list-disc ml-5 mt-2">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>

            </div>
        @endif

        <!-- Formulario -->
        <form action="{{ route('empleados.update', $empleado->id) }}" method="POST">

            @csrf
            @method('PUT')

            <!-- Nombre -->
            <div class="mb-5">

                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Nombre Completo
                </label>

                <input
                    type="text"
                    name="nombre_completo"
                    value="{{ old('nombre_completo', $empleado->nombre_completo) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>

            </div>

            <!-- Correo -->
            <div class="mb-5">

                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Correo Electrónico
                </label>

                <input
                    type="email"
                    name="correo"
                    value="{{ old('correo', $empleado->correo) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>

            </div>

            <!-- Teléfono -->
            <div class="mb-5">

                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Número de Teléfono
                </label>

                <input
                    type="text"
                    name="telefono"
                    value="{{ old('telefono', $empleado->telefono) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>

            </div>

            <!-- DUI -->
            <div class="mb-8">

                <label class="block text-sm font-bold text-gray-700 mb-2">
                    DUI
                </label>

                <input
                    type="text"
                    name="dui"
                    value="{{ old('dui', $empleado->dui) }}"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none"
                    required>

            </div>

            <!-- Botones -->
            <div class="flex gap-4">

                <button
                    type="submit"
                    class="bg-yellow-500 hover:bg-yellow-600 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">

                    ✏️ Actualizar Empleado

                </button>

                <a href="{{ route('empleados.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-6 py-3 rounded-xl transition">

                    Cancelar

                </a>

            </div>

        </form>

    </div>

</div>

@endsection