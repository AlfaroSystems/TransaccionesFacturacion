@extends('layouts.app')

@section('title', 'Registrar Empleado')

@section('content')

<div class="max-w-2xl mx-auto">

    <div class="bg-white rounded-2xl shadow-lg p-8">

        <!-- Encabezado -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-navy-800">
                Registrar Empleado
            </h1>

            <p class="text-gray-500 mt-2">
                Complete la información del nuevo empleado
            </p>
        </div>


        <form action="{{ route('empleados.store') }}" method="POST">
            @csrf


            <!-- Nombre -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Nombre Completo
                </label>

                <input
                    type="text"
                    name="nombre_completo"
                    placeholder="Ejemplo: Juan Pérez"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>


            <!-- Correo -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Correo Electrónico
                </label>

                <input 
                    type="email"
                    name="correo"
                    placeholder="correo@ejemplo.com"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>


            <!-- Teléfono -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    Número de Teléfono
                </label>

                <input 
                    type="text"
                    name="telefono"
                    placeholder="0000-0000"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>


            <!-- DUI -->
            <div class="mb-8">
                <label class="block text-sm font-bold text-gray-700 mb-2">
                    DUI
                </label>

                <input 
                    type="text"
                    name="dui"
                    placeholder="00000000-0"
                    class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>



            <!-- Botones -->
            <div class="flex gap-4">

                <button 
                    type="submit"
                    class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">
                    Guardar Empleado
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