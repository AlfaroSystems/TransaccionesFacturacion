@extends('layouts.app')

@section('title', 'Mapa de Bodega')

@section('content')
<div class="max-w-7xl mx-auto">
    <!-- Encabezado -->
    <div class="flex justify-between items-center mb-8">
        <div>
            <h1 class="text-4xl font-bold text-slate-800 flex items-center gap-2">
                <span>Mapa de Bodega</span>
                <span class="text-3xl">🗺️</span>
            </h1>
            <p class="text-gray-500 mt-2">
                Distribución espacial y posiciones físicas disponibles agrupadas por Pasillo y Estante.
            </p>
        </div>
        <a href="{{ route('locations.index') }}"
           class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-3 rounded-xl transition border border-slate-200">
            Volver a la Lista
        </a>
    </div>

    <!-- Leyenda / Resumen -->
    <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-sm mb-8 flex flex-wrap items-center justify-between gap-4">
        <div class="flex gap-6 items-center">
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-blue-500 shadow-sm"></div>
                <span class="text-sm font-semibold text-slate-600">Posición Disponible</span>
            </div>
            <div class="flex items-center gap-2">
                <div class="w-4 h-4 rounded bg-slate-200"></div>
                <span class="text-sm font-semibold text-slate-600">Inactiva (No mostrada)</span>
            </div>
        </div>
        <div class="text-sm text-slate-500">
            Total de Pasillos Habilitados: <span class="font-bold text-slate-800">{{ $locationsGrouped->count() }}</span>
        </div>
    </div>

    @forelse($locationsGrouped as $pasillo => $racks)
        <div class="bg-white rounded-2xl p-6 border border-slate-100 shadow-md mb-8">
            <!-- Título de Pasillo -->
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4 mb-6">
                <span class="bg-blue-600 text-white font-bold text-xs uppercase tracking-wide px-3.5 py-1.5 rounded-xl shadow-sm">
                    Pasillo: {{ $pasillo ?: 'Sin Asignar' }}
                </span>
                <span class="text-slate-400 text-sm">Contiene {{ $racks->count() }} estante(s)</span>
            </div>

            <!-- Grid de Estantes -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach($racks as $rack => $locations)
                    <div class="bg-slate-50 border border-slate-100 rounded-xl p-5 hover:shadow-sm transition">
                        <!-- Título del Estante -->
                        <div class="flex items-center justify-between mb-4 border-b border-slate-200 pb-2">
                            <span class="font-bold text-slate-700 text-sm">
                                Estante (Rack): {{ $rack ?: 'Sin Asignar' }}
                            </span>
                            <span class="text-xs font-semibold text-slate-500 bg-slate-200 px-2 py-0.5 rounded">
                                {{ $locations->count() }} Posiciones
                            </span>
                        </div>

                        <!-- Grid de Posiciones (Niveles y Posiciones) -->
                        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                            @foreach($locations as $location)
                                <a href="{{ route('locations.show', $location->id) }}" 
                                   class="bg-white border border-blue-100 hover:border-blue-500 p-3 rounded-lg text-center shadow-sm transition hover:scale-105 block">
                                    <div class="text-[10px] font-bold text-blue-600 uppercase tracking-wider mb-0.5">
                                        Niv: {{ $location->level ?: '-' }}
                                    </div>
                                    <div class="text-sm font-extrabold text-slate-700">
                                        Pos: {{ $location->position ?: '-' }}
                                    </div>
                                    <div class="text-[9px] text-slate-400 font-mono mt-1 truncate" title="{{ $location->code }}">
                                        {{ $location->code }}
                                    </div>
                                    <div class="text-[9px] font-semibold text-emerald-600 mt-0.5">
                                        Cap: {{ $location->capacity }}
                                    </div>
                                </a>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @empty
        <div class="bg-white rounded-2xl p-12 text-center border border-slate-100 shadow-md">
            <span class="text-4xl block mb-4">🗺️</span>
            <p class="text-slate-500 text-lg font-medium">No hay ubicaciones activas disponibles para mostrar el mapa.</p>
        </div>
    @endforelse
</div>
@endsection
