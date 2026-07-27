@extends('layouts.app')

@section('title', 'Ubicaciones')

@section('content')

<div class="max-w-7xl mx-auto">

    <!-- Mensajes de éxito -->
    @if(session('success'))
        <div class="mb-6 bg-emerald-50 border-l-4 border-emerald-500 text-emerald-800 p-4 rounded-xl shadow-sm flex items-center justify-between">
            <div class="flex items-center gap-3">
                <span class="text-xl">✅</span>
                <p class="font-medium">{{ session('success') }}</p>
            </div>
            <button onclick="this.parentElement.remove()" class="text-emerald-500 hover:text-emerald-700 font-bold">✕</button>
        </div>
    @endif

    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-slate-800">
                Ubicaciones
            </h1>
            <p class="text-gray-500 mt-2">
                Gestione la distribución y capacidad de las ubicaciones en el almacén.
            </p>
        </div>

        <div class="flex items-center gap-3">
            <a href="{{ route('locations.map') }}"
               class="bg-slate-700 hover:bg-slate-800 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <span>🗺️ Ver Mapa de Bodega</span>
            </a>
            <a href="{{ route('locations.create') }}"
               class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <span>+ Nueva Ubicación</span>
            </a>
        </div>
    </div>

    <!-- Tarjeta / Tabla -->
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
        <div class="overflow-x-auto">
            <table class="w-full">
                <thead class="bg-slate-50 border-b border-slate-100">
                    <tr>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">ID</th>
                        <th class="px-6 py-4 text-left font-bold text-slate-600 text-sm uppercase tracking-wider">Código Autogenerado</th>
                        <th class="px-6 py-4 text-left font-bold text-slate-600 text-sm uppercase tracking-wider">Almacén</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Pasillo</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Estante</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Nivel</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Posición</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Capacidad</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Estado</th>
                        <th class="px-6 py-4 text-center font-bold text-slate-600 text-sm uppercase tracking-wider">Acciones</th>
                    </tr>
                </thead>

                <tbody class="divide-y divide-slate-100">
                    @forelse($locations as $location)
                    <tr class="hover:bg-blue-50/50 transition">
                        <td class="px-6 py-4 text-center text-slate-500 font-medium">
                            {{ $location->id }}
                        </td>
                        <td class="px-6 py-4">
                            <span class="font-mono bg-slate-100 text-slate-800 px-3 py-1.5 rounded-lg text-sm font-semibold border border-slate-200">
                                {{ $location->code }}
                            </span>
                        </td>
                        <td class="px-6 py-4 text-slate-700 font-medium">
                            {{ $location->warehouse->name ?? 'Bodega ' . $location->warehouse_id }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 font-medium">
                            {{ $location->pasillo ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 font-medium">
                            {{ $location->rack ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 font-medium">
                            {{ $location->level ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 font-medium">
                            {{ $location->position ?? '-' }}
                        </td>
                        <td class="px-6 py-4 text-center text-slate-600 font-semibold">
                            {{ number_format($location->capacity) }}
                        </td>
                        <td class="px-6 py-4 text-center">
                            @if($location->is_active)
                                <span class="bg-emerald-100 text-emerald-800 text-xs px-2.5 py-1 rounded-full font-bold">Activo</span>
                            @else
                                <span class="bg-rose-100 text-rose-800 text-xs px-2.5 py-1 rounded-full font-bold">Inactivo</span>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="flex justify-center items-center gap-2">
                                <a href="{{ route('locations.show', $location->id) }}"
                                   class="bg-blue-500 hover:bg-blue-600 text-white p-2 rounded-lg shadow transition text-xs font-semibold flex items-center gap-1">
                                    👁️ Ver
                                </a>
                                <a href="{{ route('locations.edit', $location->id) }}"
                                   class="bg-amber-500 hover:bg-amber-600 text-white p-2 rounded-lg shadow transition text-xs font-semibold flex items-center gap-1">
                                    ✏️ Editar
                                </a>
                                <form action="{{ route('locations.destroy', $location->id) }}"
                                      method="POST"
                                      onsubmit="return confirm('¿Está seguro de que desea eliminar esta ubicación?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit"
                                            class="bg-rose-600 hover:bg-rose-700 text-white p-2 rounded-lg shadow transition text-xs font-semibold flex items-center gap-1">
                                        🗑️ Eliminar
                                    </button>
                                </form>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="10" class="py-12 text-center text-slate-400 text-lg">
                            No hay ubicaciones registradas en el sistema.
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

@endsection
