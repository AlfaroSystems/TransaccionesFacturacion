@extends('layouts.app')

@section('title', 'Detalle de la Ubicación')

@section('content')

<div class="max-w-3xl mx-auto">
    <div class="bg-white rounded-2xl shadow-lg overflow-hidden border border-slate-100">
        <!-- Encabezado de la Tarjeta -->
        <div class="bg-slate-800 text-white px-8 py-6 flex justify-between items-center">
            <div>
                <span class="text-xs font-bold uppercase tracking-wider text-slate-300">Detalle de Ubicación</span>
                <h1 class="text-2xl font-bold mt-1">
                    {{ $location->code }}
                </h1>
            </div>
            <div>
                @if($location->is_active)
                    <span class="bg-emerald-500 text-white text-xs px-3 py-1.5 rounded-full font-bold shadow-sm">Activa</span>
                @else
                    <span class="bg-rose-500 text-white text-xs px-3 py-1.5 rounded-full font-bold shadow-sm">Inactiva</span>
                @endif
            </div>
        </div>

        <!-- Información de la Ubicación -->
        <div class="p-8">
            <h3 class="text-lg font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Datos Generales</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-8">
                <!-- Almacén -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400">Almacén / Bodega</label>
                    <p class="text-slate-800 font-semibold text-lg mt-1">
                        {{ $location->warehouse->name ?? 'Bodega ' . $location->warehouse_id }}
                    </p>
                </div>
                
                <!-- Capacidad -->
                <div>
                    <label class="block text-xs font-bold uppercase text-slate-400">Capacidad Máxima</label>
                    <p class="text-slate-800 font-semibold text-lg mt-1">
                        {{ number_format($location->capacity) }} items
                    </p>
                </div>
            </div>

            <h3 class="text-lg font-bold text-slate-700 mb-4 border-b border-slate-100 pb-2">Distribución Física</h3>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
                <!-- Pasillo -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center">
                    <label class="block text-xs font-bold uppercase text-slate-400">Pasillo</label>
                    <p class="text-slate-800 font-bold text-xl mt-1">{{ $location->pasillo ?? '-' }}</p>
                </div>

                <!-- Estante / Rack -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center">
                    <label class="block text-xs font-bold uppercase text-slate-400">Estante</label>
                    <p class="text-slate-800 font-bold text-xl mt-1">{{ $location->rack ?? '-' }}</p>
                </div>

                <!-- Nivel -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center">
                    <label class="block text-xs font-bold uppercase text-slate-400">Nivel</label>
                    <p class="text-slate-800 font-bold text-xl mt-1">{{ $location->level ?? '-' }}</p>
                </div>

                <!-- Posición -->
                <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-center">
                    <label class="block text-xs font-bold uppercase text-slate-400">Posición</label>
                    <p class="text-slate-800 font-bold text-xl mt-1">{{ $location->position ?? '-' }}</p>
                </div>
            </div>

            <!-- Notas -->
            @if($location->notes)
                <div class="mb-8">
                    <label class="block text-xs font-bold uppercase text-slate-400 mb-1">Notas adicionales</label>
                    <div class="bg-slate-50 p-4 rounded-xl border border-slate-100 text-slate-700 leading-relaxed">
                        {{ $location->notes }}
                    </div>
                </div>
            @endif

            <!-- Fechas de auditoría -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs text-slate-400 border-t border-slate-100 pt-6 mb-8">
                <p>Fecha de registro: <span class="font-semibold text-slate-500">{{ $location->created_at ? $location->created_at->format('d/m/Y H:i:s') : 'No registrado' }}</span></p>
                <p>Última actualización: <span class="font-semibold text-slate-500">{{ $location->updated_at ? $location->updated_at->format('d/m/Y H:i:s') : 'No registrado' }}</span></p>
            </div>

            <!-- Botones -->
            <div class="flex gap-4">
                <a href="{{ route('locations.edit', $location->id) }}"
                   class="bg-amber-500 hover:bg-amber-600 text-white font-bold px-6 py-3 rounded-xl shadow-md transition flex items-center gap-2">
                    <span>✏️ Editar Ubicación</span>
                </a>
                <a href="{{ route('locations.index') }}"
                   class="bg-slate-100 hover:bg-slate-200 text-slate-700 font-bold px-6 py-3 rounded-xl transition">
                    Regresar
                </a>
            </div>
        </div>
    </div>
</div>

@endsection
