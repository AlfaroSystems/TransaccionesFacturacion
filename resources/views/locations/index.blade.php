@extends('layouts.app')
@section('title', 'Ubicaciones')
@section('content')

<div class="w-full space-y-6 animate-fade-in duration-300">
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
            @can('locations.crear')
            <button type="button" onclick="openModal('batch-location-modal')"class="bg-emerald-600 hover:bg-emerald-700 text-white font-semibold px-5 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <span>⚡ Generación Masiva</span>
            </button>
            <button type="button" onclick="openModal('create-location-modal')"class="bg-blue-600 hover:bg-blue-700 text-white font-semibold px-6 py-3 rounded-xl shadow-lg transition flex items-center gap-2">
                <span>+ Nueva Ubicación</span>
            </button>
            @endcan
        </div>
    </div>

    <!-- Tarjeta / Tabla -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="px-6 py-3 pl-10">ID</th>
                    <th class="px-6 py-3">Código Autogenerado</th>
                    <th class="px-6 py-3">Almacén</th>
                    <th class="px-6 py-3 text-center">Pasillo</th>
                    <th class="px-6 py-3 text-center">Estante</th>
                    <th class="px-6 py-3 text-center">Nivel</th>
                    <th class="px-6 py-3 text-center">Posición</th>
                    <th class="px-6 py-3 text-center">Capacidad</th>
                    <th class="px-6 py-3 text-center">Estado</th>
                    <th class="px-6 py-3 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($locations as $location)
                    <tr class="group hover:scale-[1.005] hover:shadow-md transition-all duration-200 {{ !$location->is_active ? 'opacity-50 grayscale-[35%]' : '' }}">
                        <td class="px-6 py-4 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm text-slate-400 font-bold">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 rounded-lg bg-teal-50 flex items-center justify-center text-teal-500">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z" />
                                    </svg>
                                </div>
                                <span>#{{ $location->id }}</span>
                            </div>
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100">
                            <span class="font-mono bg-slate-100 text-slate-800 px-3 py-1.5 rounded-lg text-sm font-semibold border border-slate-200">
                                {{ $location->code }}
                            </span>
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm font-bold text-slate-700">
                            {{ $location->warehouse->name ?? '-' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-sm font-medium text-slate-600">
                            {{ $location->type_label }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center text-slate-600 font-medium">
                            {{ $location->aisle ?? '-' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center text-slate-600 font-medium">
                            {{ $location->rack ?? '-' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center text-slate-600 font-medium">
                            {{ $location->level ?? '-' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center text-slate-600 font-medium">
                            {{ $location->position ?? '-' }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center text-slate-700 font-bold">
                            {{ number_format($location->capacity) }}
                        </td>
                        <td class="px-6 py-4 bg-white border-y border-slate-100 text-center">
                            <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full text-xs font-bold {{ $location->is_active ? 'bg-emerald-50 text-emerald-700 border border-emerald-100' : 'bg-rose-50 text-rose-700 border border-rose-100' }}">
                                <span class="w-1.5 h-1.5 rounded-full {{ $location->is_active ? 'bg-emerald-500' : 'bg-rose-500' }}"></span>
                                {{ $location->is_active ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex justify-center items-center gap-2">
                                <!-- Ver -->
                                @can('locations.ver')
                                <a href="{{ route('locations.show', $location->id) }}" class="p-2 text-teal-600 bg-teal-50 hover:bg-teal-100 border border-teal-100/50 rounded-xl transition-all" title="Ver Detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                </a>
                                @endcan

                                <!-- Editar -->
                                @can('locations.editar')
                                <button type="button" onclick="openEditLocationModal('{{ route('locations.update', $location->id) }}', {{ json_encode($location) }})" class="p-2 text-blue-600 bg-blue-50 hover:bg-blue-100 border border-blue-100/50 rounded-xl transition-all" title="Editar Ubicación">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                    </svg>
                                </button>
                                @endcan

                                <!-- Eliminar / Inactivar -->
                                @can('locations.eliminar')
                                    @if($location->is_active)
                                        <button type="button" onclick="confirmDelete('{{ route('locations.destroy', $location->id) }}', '{{ $location->code }}')" class="p-2 text-rose-600 bg-rose-50 hover:bg-rose-100 border border-rose-100/50 rounded-xl transition-all" title="Inactivar Ubicación">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636" />
                                            </svg>
                                        </button>
                                    @else
                                        <button type="button" onclick="confirmDelete('{{ route('locations.destroy', $location->id) }}', '{{ $location->code }}')" class="p-2 text-emerald-600 bg-emerald-50 hover:bg-emerald-100 border border-emerald-100/50 rounded-xl transition-all" title="Reactivar Ubicación">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z" />
                                            </svg>
                                        </button>
                                    @endif
                                @endcan
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="py-12 bg-white rounded-2xl border border-slate-100 text-center text-slate-400 font-semibold shadow-sm">
                            No hay ubicaciones registradas en el sistema.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>

<!-- MODAL DE REGISTRO DE UBICACIÓN -->
<div id="create-location-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('create-location-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-blue-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Registrar Ubicación</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Define las coordenadas espaciales para el inventario.</p>
            </div>
        </div>
        <form action="{{ route('locations.store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="create">
            <!-- Almacén / Bodega -->
            <div>
                <label for="warehouse_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Almacén / Bodega *</label>
                <select name="warehouse_id" id="warehouse_id" class="w-full bg-slate-50 border @error('warehouse_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione un almacén...</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (old('modal_type') === 'create' && old('warehouse_id') == $warehouse->id) ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Código de Ubicación -->
            <div>
                <label for="code" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Código de Ubicación *</label>
                <input type="text" name="code" id="code" value="{{ old('modal_type') === 'create' ? old('code') : '' }}" placeholder="Ej: UB-A1" class="w-full bg-slate-50 border @error('code') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white text-slate-700 font-semibold" required>
                @error('code')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Pasillo -->
                <div>
                    <label for="pasillo" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pasillo (Aisle)</label>
                    <input type="text" name="pasillo" id="pasillo" value="{{ old('modal_type') === 'create' ? old('pasillo') : '' }}" placeholder="Ej: 2" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>

                <!-- Estante -->
                <div>
                    <label for="rack" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estante (Rack)</label>
                    <input type="text" name="rack" id="rack" value="{{ old('modal_type') === 'create' ? old('rack') : '' }}" placeholder="Ej: 3" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Nivel -->
                <div>
                    <label for="level" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nivel (Level)</label>
                    <input type="text" name="level" id="level" value="{{ old('modal_type') === 'create' ? old('level') : '' }}" placeholder="Ej: 1" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>

                <!-- Posición -->
                <div>
                    <label for="position" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Posición (Position)</label>
                    <input type="text" name="position" id="position" value="{{ old('modal_type') === 'create' ? old('position') : '' }}" placeholder="Ej: 4" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Capacidad -->
            <div>
                <label for="capacity" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Capacidad Máxima *</label>
                <input type="number" name="capacity" id="capacity" min="0" value="{{ old('modal_type') === 'create' ? old('capacity') : '' }}" placeholder="Ej: 50" class="w-full bg-slate-50 border @error('capacity') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2 text-sm focus:outline-none focus:bg-white text-slate-700 font-semibold" required>
                @error('capacity')
                    @if(old('modal_type') === 'create')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Notas -->
            <div>
                <label for="notes" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notas adicionales</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Comentarios..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] text-slate-700 font-semibold">{{ old('modal_type') === 'create' ? old('notes') : '' }}</textarea>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('create-location-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Ubicación
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE EDICIÓN DE UBICACIÓN -->
<div id="edit-location-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('edit-location-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-[#005e66] flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Editar Ubicación</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Modifica los parámetros de la ubicación física.</p>
            </div>
        </div>
        <form action="" method="POST" class="space-y-4">
            @csrf
            @method('PUT')
            <input type="hidden" name="modal_type" value="edit">
            <input type="hidden" name="id" id="edit-id" value="{{ old('modal_type') === 'edit' ? old('id') : '' }}">

            <!-- Almacén / Bodega -->
            <div>
                <label for="edit-warehouse_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Almacén / Bodega *</label>
                <select name="warehouse_id" id="edit-warehouse_id" class="w-full bg-slate-50 border @error('warehouse_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione un almacén...</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (old('modal_type') === 'edit' && old('warehouse_id') == $warehouse->id) ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
                @error('warehouse_id')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Código de Ubicación -->
            <div>
                <label for="edit-code" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Código de Ubicación *</label>
                <input type="text" name="code" id="edit-code" value="{{ old('modal_type') === 'edit' ? old('code') : '' }}" placeholder="Ej: UB-A1" class="w-full bg-slate-50 border @error('code') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white text-slate-700 font-semibold" required>
                @error('code')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>
            <div class="grid grid-cols-2 gap-4">
                <!-- Pasillo -->
                <div>
                    <label for="edit-pasillo" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pasillo (Aisle)</label>
                    <input type="text" name="pasillo" id="edit-pasillo" value="{{ old('modal_type') === 'edit' ? old('pasillo') : '' }}" placeholder="Ej: 2" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>

                <!-- Estante -->
                <div>
                    <label for="edit-rack" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estante (Rack)</label>
                    <input type="text" name="rack" id="edit-rack" value="{{ old('modal_type') === 'edit' ? old('rack') : '' }}" placeholder="Ej: 3" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <!-- Nivel -->
                <div>
                    <label for="edit-level" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nivel (Level)</label>
                    <input type="text" name="level" id="edit-level" value="{{ old('modal_type') === 'edit' ? old('level') : '' }}" placeholder="Ej: 1" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>

                <!-- Posición -->
                <div>
                    <label for="edit-position" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Posición (Position)</label>
                    <input type="text" name="position" id="edit-position" value="{{ old('modal_type') === 'edit' ? old('position') : '' }}" placeholder="Ej: 4" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold">
                </div>
            </div>

            <!-- Capacidad -->
            <div>
                <label for="edit-capacity" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Capacidad Máxima *</label>
                <input type="number" name="capacity" id="edit-capacity" min="0" value="{{ old('modal_type') === 'edit' ? old('capacity') : '' }}" placeholder="Ej: 50" class="w-full bg-slate-50 border @error('capacity') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2 text-sm focus:outline-none focus:bg-white text-slate-700 font-semibold" required>
                @error('capacity')
                    @if(old('modal_type') === 'edit')
                        <p class="text-rose-500 text-xs mt-1 font-semibold ml-2">{{ $message }}</p>
                    @endif
                @enderror
            </div>

            <!-- Notas -->
            <div>
                <label for="edit-notes" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notas adicionales</label>
                <textarea name="notes" id="edit-notes" rows="2" placeholder="Comentarios..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] text-slate-700 font-semibold">{{ old('modal_type') === 'edit' ? old('notes') : '' }}</textarea>
            </div>

            <!-- Estado -->
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-3">Estado de la Ubicación</label>
                <label class="flex items-center gap-3 cursor-pointer">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" id="edit-is_active" value="1" class="sr-only peer" {{ old('modal_type') === 'edit' ? (old('is_active') ? 'checked' : '') : '' }}>
                    <div class="relative w-11 h-6 bg-slate-200 peer-focus:outline-none rounded-full peer peer-checked:after:translate-x-full rtl:peer-checked:after:-translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-slate-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-500"></div>
                    <span class="text-sm font-semibold text-slate-600" id="edit-is_active_label">Ubicación Activa</span>
                </label>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('edit-location-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-[#005e66] hover:bg-[#3cb0a4] text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7H5a2 2 0 00-2 2v9a2 2 0 002 2h14a2 2 0 002-2V9a2 2 0 00-2-2h-3m-1 4l-3 3m0 0l-3-3m3 3V4" /></svg>
                    Guardar Cambios
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL DE GENERACIÓN MASIVA DE UBICACIONES -->
<div id="batch-location-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white rounded-3xl p-8 max-w-lg w-full shadow-2xl relative mx-4 transform scale-95 transition-all duration-200">
        <!-- Close Button -->
        <button type="button" onclick="closeModal('batch-location-modal')" class="absolute top-6 right-6 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" /></svg>
        </button>
        <div class="flex items-center gap-4 mb-6">
            <div class="w-12 h-12 rounded-2xl bg-emerald-600 flex items-center justify-center text-white">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" />
                </svg>
            </div>
            <div>
                <h2 class="text-2xl font-extrabold text-slate-800 tracking-tight">Generación Masiva</h2>
                <p class="text-slate-400 text-sm font-semibold mt-1">Crea automáticamente todas las ubicaciones hasta los límites indicados.</p>
            </div>
        </div>
        <form action="{{ route('locations.batch-store') }}" method="POST" class="space-y-4">
            @csrf
            <input type="hidden" name="modal_type" value="batch">
            <!-- Almacén / Bodega -->
            <div>
                <label for="batch_warehouse_id" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Almacén / Bodega *</label>
                <select name="warehouse_id" id="batch_warehouse_id" class="w-full bg-slate-50 border @error('warehouse_id') border-rose-300 focus:border-rose-500 @else border-slate-200 focus:border-[#005e66] @enderror rounded-xl px-4 py-2.5 text-sm focus:outline-none focus:bg-white transition-all text-slate-700 font-semibold" required>
                    <option value="">Seleccione un almacén...</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id }}" {{ (old('modal_type') === 'batch' && old('warehouse_id') == $warehouse->id) ? 'selected' : '' }}>
                            {{ $warehouse->name }}
                        </option>
                    @endforeach
                </select>
            </div>

            <div class="grid grid-cols-2 gap-4">
                <!-- Pasillo Hasta -->
                <div>
                    <label for="pasillo_hasta" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Pasillo Final *</label>
                    <input type="text" name="pasillo_hasta" id="pasillo_hasta" value="{{ old('modal_type') === 'batch' ? old('pasillo_hasta') : 'B' }}" placeholder="Ej: B o 2" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold uppercase" required>
                    <p class="text-[10px] text-slate-400 mt-1">Letra (ej. B) o Número (ej. 2)</p>
                </div>

                <!-- Estante Hasta -->
                <div>
                    <label for="rack_hasta" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Estante Final *</label>
                    <input type="number" name="rack_hasta" id="rack_hasta" min="1" value="{{ old('modal_type') === 'batch' ? old('rack_hasta') : '3' }}" placeholder="Ej: 3" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold" required>
                    <p class="text-[10px] text-slate-400 mt-1">Generará del 1 hasta N</p>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4">
                <!-- Nivel Hasta -->
                <div>
                    <label for="level_hasta" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Nivel Final *</label>
                    <input type="number" name="level_hasta" id="level_hasta" min="1" value="{{ old('modal_type') === 'batch' ? old('level_hasta') : '4' }}" placeholder="Ej: 4" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold" required>
                    <p class="text-[10px] text-slate-400 mt-1">Generará del 1 hasta N</p>
                </div>

                <!-- Posición Hasta -->
                <div>
                    <label for="position_hasta" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Posición Final *</label>
                    <input type="number" name="position_hasta" id="position_hasta" min="1" value="{{ old('modal_type') === 'batch' ? old('position_hasta') : '1' }}" placeholder="Ej: 1" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold" required>
                    <p class="text-[10px] text-slate-400 mt-1">Generará del 1 hasta N</p>
                </div>
            </div>

            <!-- Capacidad -->
            <div>
                <label for="batch_capacity" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Capacidad por Ubicación *</label>
                <input type="number" name="capacity" id="batch_capacity" min="0" value="{{ old('modal_type') === 'batch' ? old('capacity') : '10' }}" placeholder="Ej: 10" class="w-full bg-slate-50 border border-slate-200 focus:border-[#005e66] rounded-xl px-4 py-2 text-sm focus:outline-none text-slate-700 font-semibold" required>
            </div>

            <!-- Notas -->
            <div>
                <label for="batch_notes" class="block text-xs font-bold text-slate-400 uppercase tracking-wider mb-2">Notas adicionales</label>
                <textarea name="notes" id="batch_notes" rows="2" placeholder="Comentarios..." class="w-full bg-slate-50 border border-slate-200 rounded-xl px-4 py-2 text-sm focus:outline-none focus:border-[#005e66] text-slate-700 font-semibold">{{ old('modal_type') === 'batch' ? old('notes') : '' }}</textarea>
            </div>

            <!-- Botones -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
                <button type="button" onclick="closeModal('batch-location-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-sm transition-all text-center">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white rounded-full font-bold text-sm shadow-md hover:shadow-lg transition-all transform active:scale-95 flex items-center gap-2">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z" /></svg>
                    Generar Ubicaciones
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function setupAutoCodeGenerator(prefix) {
        const pasilloInput = document.getElementById(prefix ? `${prefix}-pasillo` : 'pasillo');
        const rackInput = document.getElementById(prefix ? `${prefix}-rack` : 'rack');
        const levelInput = document.getElementById(prefix ? `${prefix}-level` : 'level');
        const positionInput = document.getElementById(prefix ? `${prefix}-position` : 'position');
        const codeInput = document.getElementById(prefix ? `${prefix}-code` : 'code');

        if (!codeInput) return;

        function generateCode() {
            const parts = [];
            if (pasilloInput && pasilloInput.value.trim() !== '') parts.push(pasilloInput.value.trim().toUpperCase());
            if (rackInput && rackInput.value.trim() !== '') parts.push(rackInput.value.trim());
            if (levelInput && levelInput.value.trim() !== '') parts.push(levelInput.value.trim());
            if (positionInput && positionInput.value.trim() !== '') parts.push(positionInput.value.trim());

            if (parts.length > 0) {
                codeInput.value = parts.join('-');
            }
        }

        [pasilloInput, rackInput, levelInput, positionInput].forEach(input => {
            if (input) {
                input.addEventListener('input', generateCode);
            }
        });
    }

    function openEditLocationModal(actionUrl, location) {
        const modal = document.getElementById('edit-location-modal');
        modal.querySelector('form').action = actionUrl;
        document.getElementById('edit-id').value = location.id;
        document.getElementById('edit-warehouse_id').value = location.warehouse_id;
        document.getElementById('edit-code').value = location.code || '';
        document.getElementById('edit-pasillo').value = location.pasillo || '';
        document.getElementById('edit-rack').value = location.rack || '';
        document.getElementById('edit-level').value = location.level || '';
        document.getElementById('edit-position').value = location.position || '';
        document.getElementById('edit-capacity').value = location.capacity;
        document.getElementById('edit-notes').value = location.notes || '';
        
        const isActiveChk = document.getElementById('edit-is_active');
        isActiveChk.checked = location.is_active == 1;
        
        const label = document.getElementById('edit-is_active_label');
        label.textContent = location.is_active == 1 ? 'Ubicación Activa' : 'Ubicación Inactiva';
        
        openModal('edit-location-modal');
    }

    document.addEventListener('DOMContentLoaded', () => {
        setupAutoCodeGenerator('');
        setupAutoCodeGenerator('edit');

        const isActiveChk = document.getElementById('edit-is_active');
        const label = document.getElementById('edit-is_active_label');
        if (isActiveChk && label) {
            isActiveChk.addEventListener('change', () => {
                label.textContent = isActiveChk.checked ? 'Ubicación Activa' : 'Ubicación Inactiva';
            });
        }
    });
</script>

@if($errors->any())
    <script>
        window.addEventListener('DOMContentLoaded', () => {
            @if(old('modal_type') === 'edit')
                const editRoute = "{{ route('locations.update', old('id', 0)) }}";
                const oldLocation = {
                    id: "{{ old('id') }}",
                    warehouse_id: "{{ old('warehouse_id') }}",
                    code: "{{ old('code') }}",
                    pasillo: "{{ old('pasillo') }}",
                    rack: "{{ old('rack') }}",
                    level: "{{ old('level') }}",
                    position: "{{ old('position') }}",
                    capacity: "{{ old('capacity') }}",
                    notes: "{{ old('notes') }}",
                    is_active: "{{ old('is_active', '0') }}"
                };
                openEditLocationModal(editRoute, oldLocation);
            @elseif(old('modal_type') === 'batch')
                openModal('batch-location-modal');
            @else
                openModal('create-location-modal');
            @endif
        });
    </script>
@endif
@endsection