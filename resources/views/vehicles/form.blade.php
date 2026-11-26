@extends('layouts.app')
@section('title', $vehicle ? 'Editar Vehículo' : 'Nuevo Vehículo')

@php
    $inputClass = 'w-full px-3 py-2.5 rounded-xl border border-line dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $labelClass = 'ui-label';
    $activo = old('is_active', $vehicle?->is_active ?? true);
    // Espacios de carga: los enviados (si volvió con errores) o los que ya tiene
    $espacios = old('locations', $vehicle
        ? $vehicle->locations->sortBy('code')->map(fn ($l) => [
            'id_vehicle_location' => $l->id_vehicle_location,
            'id_warehouse' => $l->id_warehouse,
            'code' => $l->code,
            'capacity' => $l->capacity,
            'notes' => $l->notes,
            'is_active' => $l->is_active,
        ])->values()->all()
        // Uno nuevo empieza con un espacio por llenar (sin código no se guarda)
        : [['id_warehouse' => null, 'code' => '', 'capacity' => 0, 'notes' => '', 'is_active' => true]]);
    $siguiente = count($espacios) ? max(array_keys($espacios)) + 1 : 0;
@endphp

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Vehículos</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $vehicle ? $vehicle->license_plate : 'Nuevo' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-primary dark:text-accent dark:text-teal-400 tracking-tight mt-1">{{ $vehicle ? 'Editar Vehículo' : 'Nuevo Vehículo' }}</h1>
        </div>
        <a href="{{ route('vehicles.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver</a>
    </div>

    <form method="POST" action="{{ $vehicle ? route('vehicles.update', $vehicle) : route('vehicles.store') }}" class="space-y-6">
        @csrf
        @if($vehicle)
            @method('PUT')
        @endif

        <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs p-6 grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
                <label for="license_plate" class="{{ $labelClass }}">Placa *</label>
                <input type="text" id="license_plate" name="license_plate" required maxlength="20" value="{{ old('license_plate', $vehicle?->license_plate) }}" placeholder="P123-456" class="{{ $inputClass }} font-mono uppercase @error('license_plate') border-rose-400 @enderror">
                @error('license_plate')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="brand" class="{{ $labelClass }}">Marca *</label>
                <input type="text" id="brand" name="brand" required maxlength="100" value="{{ old('brand', $vehicle?->brand) }}" class="{{ $inputClass }} @error('brand') border-rose-400 @enderror">
                @error('brand')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="model" class="{{ $labelClass }}">Modelo *</label>
                <input type="text" id="model" name="model" required maxlength="100" value="{{ old('model', $vehicle?->model) }}" class="{{ $inputClass }} @error('model') border-rose-400 @enderror">
                @error('model')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="year" class="{{ $labelClass }}">Año</label>
                <input type="number" id="year" name="year" min="1950" max="{{ now()->year + 1 }}" value="{{ old('year', $vehicle?->year) }}" class="{{ $inputClass }} @error('year') border-rose-400 @enderror">
                @error('year')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div>
                <label for="id_responsible_employee" class="{{ $labelClass }}">Responsable</label>
                <select id="id_responsible_employee" name="id_responsible_employee" class="{{ $inputClass }} @error('id_responsible_employee') border-rose-400 @enderror">
                    <option value="">-- Sin asignar --</option>
                    @foreach($empleados as $empleado)
                        <option value="{{ $empleado->id_employee }}" @selected((string) old('id_responsible_employee', $vehicle?->id_responsible_employee) === (string) $empleado->id_employee)>{{ $empleado->fullName() }} ({{ $empleado->branch?->name }})</option>
                    @endforeach
                </select>
                @error('id_responsible_employee')
                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            <div class="flex items-end">
                <label class="flex items-center gap-3 cursor-pointer py-2.5">
                    <input type="hidden" name="is_active" value="0">
                    <input type="checkbox" name="is_active" value="1" @checked($activo) class="w-4 h-4 rounded border-slate-300 text-primary dark:text-accent">
                    <span class="text-sm font-semibold text-slate-600 dark:text-slate-300">Vehículo activo</span>
                </label>
            </div>
        </div>

        <!-- Espacios de carga -->
        <div class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-line dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Espacios de carga</h2>
                <p class="text-xs text-slate-400">Cada espacio (cajón, plataforma, compartimento) pertenece a una bodega y es una ubicación de inventario. Capacidad 0 es sin límite.</p>
                @error('locations')
                    <p class="text-rose-500 text-xs font-semibold mt-2">{{ $message }}</p>
                @enderror
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-line dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4 w-40">Código *</th>
                            <th class="py-3 px-4">Bodega *</th>
                            <th class="py-3 px-4 w-32 text-right">Capacidad</th>
                            <th class="py-3 px-4">Notas</th>
                            <th class="py-3 px-4 w-20 text-center">Activo</th>
                            <th class="py-3 px-4 w-12"></th>
                        </tr>
                    </thead>
                    <tbody id="espaciosBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($espacios as $i => $espacio)
                            <tr>
                                <td class="py-3 px-4">
                                    @if(! empty($espacio['id_vehicle_location']))
                                        <input type="hidden" name="locations[{{ $i }}][id_vehicle_location]" value="{{ $espacio['id_vehicle_location'] }}">
                                    @endif
                                    <input type="text" name="locations[{{ $i }}][code]" value="{{ $espacio['code'] ?? '' }}" maxlength="50" class="{{ $inputClass }} font-mono uppercase @error("locations.$i.code") border-rose-400 @enderror">
                                    @error("locations.$i.code")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-3 px-4">
                                    <select name="locations[{{ $i }}][id_warehouse]" class="{{ $inputClass }} @error("locations.$i.id_warehouse") border-rose-400 @enderror">
                                        <option value="">-- Elija la bodega --</option>
                                        @foreach($bodegas as $bodega)
                                            <option value="{{ $bodega->id_warehouse }}" @selected((string) ($espacio['id_warehouse'] ?? '') === (string) $bodega->id_warehouse)>{{ $bodega->name }} ({{ $bodega->branch?->name }})</option>
                                        @endforeach
                                    </select>
                                    @error("locations.$i.id_warehouse")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" min="0" name="locations[{{ $i }}][capacity]" value="{{ $espacio['capacity'] ?? 0 }}" class="{{ $inputClass }} text-right font-mono">
                                </td>
                                <td class="py-3 px-4">
                                    <input type="text" name="locations[{{ $i }}][notes]" value="{{ $espacio['notes'] ?? '' }}" maxlength="500" class="{{ $inputClass }}">
                                </td>
                                <td class="py-3 px-4 text-center">
                                    <input type="hidden" name="locations[{{ $i }}][is_active]" value="0">
                                    <input type="checkbox" name="locations[{{ $i }}][is_active]" value="1" @checked($espacio['is_active'] ?? true) class="w-4 h-4 rounded border-slate-300 text-primary dark:text-accent">
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button type="button" class="espacio-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar espacio">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            <div class="px-6 py-4 border-t border-line dark:border-slate-800">
                <button type="button" id="espacioAgregar" class="text-xs font-bold text-primary dark:text-accent dark:text-teal-400 hover:underline">+ Agregar espacio</button>
            </div>

            <template id="espacioPlantilla">
                <tr>
                    <td class="py-3 px-4">
                        <input type="text" name="locations[__i__][code]" maxlength="50" class="{{ $inputClass }} font-mono uppercase">
                    </td>
                    <td class="py-3 px-4">
                        <select name="locations[__i__][id_warehouse]" class="{{ $inputClass }}">
                            <option value="">-- Elija la bodega --</option>
                            @foreach($bodegas as $bodega)
                                <option value="{{ $bodega->id_warehouse }}">{{ $bodega->name }} ({{ $bodega->branch?->name }})</option>
                            @endforeach
                        </select>
                    </td>
                    <td class="py-3 px-4">
                        <input type="number" min="0" name="locations[__i__][capacity]" value="0" class="{{ $inputClass }} text-right font-mono">
                    </td>
                    <td class="py-3 px-4">
                        <input type="text" name="locations[__i__][notes]" maxlength="500" class="{{ $inputClass }}">
                    </td>
                    <td class="py-3 px-4 text-center">
                        <input type="hidden" name="locations[__i__][is_active]" value="0">
                        <input type="checkbox" name="locations[__i__][is_active]" value="1" checked class="w-4 h-4 rounded border-slate-300 text-primary dark:text-accent">
                    </td>
                    <td class="py-3 px-4 text-right">
                        <button type="button" class="espacio-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar espacio">✕</button>
                    </td>
                </tr>
            </template>
        </div>

        <div class="flex justify-end gap-3">
            <a href="{{ route('vehicles.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 border border-line dark:border-slate-700">Cancelar</a>
            <button type="submit" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">{{ $vehicle ? 'Guardar cambios' : 'Registrar vehículo' }}</button>
        </div>
    </form>
</div>

<script>
    // Espacios de carga: agregar y quitar filas
    (function () {
        const body = document.getElementById('espaciosBody');
        const plantilla = document.getElementById('espacioPlantilla');
        let siguiente = {{ $siguiente }};

        document.getElementById('espacioAgregar').addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__i__', siguiente++));
        });

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('espacio-quitar')) {
                e.target.closest('tr').remove();
            }
        });
    })();
</script>
@endsection
