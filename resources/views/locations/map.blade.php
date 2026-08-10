@extends('layouts.app')

@section('title', 'Mapa de Bodega')

@section('content')
<div class="animate-fade-in duration-300">
    <!-- Encabezado -->
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Mapa Visual de Bodega</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Representación esquemática y estado de capacidad de las ubicaciones físicas.</p>
        </div>

        <a href="{{ route('locations.index') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-sm transition-all">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16" /></svg>
            <span>Ver Tabla de Ubicaciones</span>
        </a>
    </header>

    <!-- Rejilla de Ubicaciones en Bodega -->
    <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
        @forelse($locations as $location)
            <div class="bg-white rounded-2xl p-5 border border-slate-100 shadow-sm hover:shadow-md transition-all">
                <div class="flex items-center justify-between mb-3">
                    <span class="px-3 py-1 rounded-full text-xs font-mono font-bold bg-[#005e66] text-white">
                        {{ $location->code }}
                    </span>
                    <span class="text-xs font-bold text-emerald-600 bg-emerald-50 px-2.5 py-1 rounded-full">
                        Cap: {{ $location->capacity }}
                    </span>
                </div>

                <h3 class="text-sm font-bold text-slate-800 mb-1">
                    {{ $location->warehouse->name ?? 'Bodega GENERAL' }}
                </h3>

                <div class="text-xs text-slate-500 space-y-1 mt-3 pt-3 border-t border-slate-100">
                    <p><span class="font-bold text-slate-600">Pasillo:</span> {{ $location->pasillo ?? 'N/A' }} | <span class="font-bold text-slate-600">Estante:</span> {{ $location->rack ?? 'N/A' }}</p>
                    <p><span class="font-bold text-slate-600">Nivel:</span> {{ $location->level ?? 'N/A' }} | <span class="font-bold text-slate-600">Posición:</span> {{ $location->position ?? 'N/A' }}</p>
                </div>
            </div>
        @empty
            <div class="col-span-full bg-white rounded-2xl p-12 text-center text-slate-400 font-semibold border border-slate-100">
                No hay ubicaciones físicas activas para mostrar en el mapa.
            </div>
        @endforelse
    </div>
</div>
@endsection
