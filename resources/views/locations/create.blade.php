@extends('layouts.app')

@section('title', 'Nueva Ubicación')

@section('content')

<div class="max-w-2xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg p-8 border border-slate-100">
        <!-- Encabezado -->
        <div class="mb-8">
            <h1 class="text-3xl font-extrabold text-slate-800">
                Nueva Ubicación
            </h1>
            <p class="text-gray-500 mt-2">
                Defina los parámetros para crear y autogenerar el código de la nueva ubicación.
            </p>
        </div>

        <!-- Mensajes de error -->
        @if ($errors->any())
            <div class="mb-6 bg-rose-50 border-l-4 border-rose-500 text-rose-800 p-4 rounded-xl shadow-sm">
                <strong class="font-bold">Por favor corrija los siguientes errores:</strong>
                <ul class="list-disc ml-5 mt-2 text-sm text-rose-700">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        <form action="{{ route('locations.store') }}" method="POST">
            @csrf

            <!-- Almacén / Bodega -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    Almacén / Bodega *
                </label>
                <select name="warehouse_id" required
                        class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none bg-white">
                    <option value="" disabled selected>Seleccione un almacén...</option>
                    @forelse($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ old('warehouse_id') == $warehouse->id ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @empty
                        <!-- Opción de respaldo en caso de que no existan registros de almacén aún -->
                        <option value="1" {{ old('warehouse_id') == 1 ? 'selected' : '' }}>Bodega Principal (Predeterminada)</option>
                        <option value="2" {{ old('warehouse_id') == 2 ? 'selected' : '' }}>Bodega Secundaria</option>
                    @endforelse
                </select>
            </div>

            <!-- Fila de Campos de Coordenadas de Ubicación -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Pasillo -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Pasillo (Aisle)
                    </label>
                    <input type="text" name="pasillo" value="{{ old('pasillo') }}" placeholder="Ej: 2"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <!-- Estante (Rack) -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Estante (Rack / Shelf)
                    </label>
                    <input type="text" name="rack" value="{{ old('rack') }}" placeholder="Ej: 3"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Fila de Nivel y Posición -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-5 mb-5">
                <!-- Nivel -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Nivel (Level)
                    </label>
                    <input type="text" name="level" value="{{ old('level') }}" placeholder="Ej: 1"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>

                <!-- Posición -->
                <div>
                    <label class="block text-sm font-bold text-slate-700 mb-2">
                        Posición (Position)
                    </label>
                    <input type="text" name="position" value="{{ old('position') }}" placeholder="Ej: 4"
                           class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
                </div>
            </div>

            <!-- Capacidad -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    Capacidad Máxima (Items) *
                </label>
                <input type="number" name="capacity" value="{{ old('capacity') }}" min="0" placeholder="Ej: 50" required
                       class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">
            </div>

            <!-- Notas -->
            <div class="mb-5">
                <label class="block text-sm font-bold text-slate-700 mb-2">
                    Notas adicionales
                </label>
                <textarea name="notes" rows="3" placeholder="Comentarios o indicaciones..."
                          class="w-full px-4 py-3 border border-gray-300 rounded-xl focus:ring-2 focus:ring-blue-500 focus:outline-none">{{ old('notes') }}</textarea>
            </div>

            <!-- Estado Activo/Inactivo (Checkbox) -->
            <div class="mb-8 flex items-center gap-3">
                <input type="checkbox" name="is_active" value="1" id="is_active" {{ old('is_active', '1') == '1' ? 'checked' : '' }}
                       class="w-5 h-5 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                <label for="is_active" class="text-sm font-bold text-slate-700 cursor-pointer select-none">
                    Ubicación habilitada / activa
                </label>
            </div>

            <!-- Botones -->
            <div class="flex gap-4">
                <button type="submit"
                        class="bg-blue-600 hover:bg-blue-700 text-white font-bold px-6 py-3 rounded-xl shadow-md transition">
                    Guardar Ubicación
                </button>
                <a href="{{ route('locations.index') }}"
                   class="bg-gray-200 hover:bg-gray-300 text-gray-700 font-bold px-6 py-3 rounded-xl transition">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>

@endsection
