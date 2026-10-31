@extends('layouts.app')
@section('title', $transfer ? 'Editar Traslado' : 'Nuevo Traslado')

@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $inputClass = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    // En edición, lo que ya tiene el borrador por lote y ubicación de origen
    $actuales = $transfer
        ? $transfer->details->keyBy(fn ($d) => \App\Services\InventoryTransferService::clave($d->id_inventory_lot, $d->id_source_location))
        : collect();
@endphp

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Traslados</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">{{ $transfer ? $transfer->transfer_code : 'Nuevo' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">{{ $transfer ? 'Editar Traslado' : 'Nuevo Traslado' }}</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Se guarda como borrador; la mercadería sale de la bodega origen al despacharlo.</p>
        </div>
        <a href="{{ $transfer ? route('inventory-transfers.show', $transfer) : route('inventory-transfers.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver</a>
    </div>

    {{-- Paso 1: bodegas. Al cambiarlas se recarga la existencia de la bodega origen --}}
    @unless($transfer)
        <form method="GET" action="{{ route('inventory-transfers.create') }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 grid grid-cols-1 md:grid-cols-3 gap-4 items-end">
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bodega origen *</label>
                <select name="id_source_warehouse" onchange="this.form.submit()" class="{{ $inputClass }}">
                    <option value="">-- Elija la bodega --</option>
                    @foreach($origenes as $bodega)
                        <option value="{{ $bodega->id_warehouse }}" @selected($origen?->id_warehouse === $bodega->id_warehouse)>{{ $bodega->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bodega destino *</label>
                <select name="id_destination_warehouse" onchange="this.form.submit()" class="{{ $inputClass }}" @disabled(! $origen)>
                    <option value="">{{ $origen ? '-- Elija la bodega --' : 'Elija primero la bodega origen' }}</option>
                    @foreach($destinos as $bodega)
                        <option value="{{ $bodega->id_warehouse }}" @selected($destino?->id_warehouse === $bodega->id_warehouse)>{{ $bodega->name }} ({{ $bodega->branch?->name }})</option>
                    @endforeach
                </select>
            </div>
            <noscript><button type="submit" class="bg-slate-800 text-white font-semibold py-2.5 px-5 rounded-xl text-sm">Continuar</button></noscript>
        </form>
    @endunless

    {{-- Paso 2: lo que sale de cada lote --}}
    @if($origen && $destino)
        <form method="POST" action="{{ $transfer ? route('inventory-transfers.update', $transfer) : route('inventory-transfers.store') }}" class="space-y-6">
            @csrf
            @if($transfer)
                @method('PUT')
            @endif
            <input type="hidden" name="id_source_warehouse" value="{{ $origen->id_warehouse }}">
            <input type="hidden" name="id_destination_warehouse" value="{{ $destino->id_warehouse }}">

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 grid grid-cols-1 md:grid-cols-3 gap-4">
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Ruta</label>
                    <p class="text-sm font-bold text-slate-800 dark:text-white py-2.5">{{ $origen->name }} <span class="text-slate-400">→</span> {{ $destino->name }}</p>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Fecha del traslado *</label>
                    <input type="date" name="transfer_date" required value="{{ old('transfer_date', $transfer?->transfer_date?->format('Y-m-d') ?? now()->format('Y-m-d')) }}" class="{{ $inputClass }}">
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Vehículo o ubicación de tránsito</label>
                    <select name="id_vehicle_location" class="{{ $inputClass }}">
                        <option value="">-- Sin indicar --</option>
                        @foreach($ubicacionesVehiculo->groupBy(fn ($l) => $l->warehouse?->name) as $bodega => $ubicaciones)
                            <optgroup label="{{ $bodega }}">
                                @foreach($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id_location }}" @selected((string) old('id_vehicle_location', $transfer?->id_vehicle_location) === (string) $ubicacion->id_location)>{{ $ubicacion->code }}</option>
                                @endforeach
                            </optgroup>
                        @endforeach
                    </select>
                </div>
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Notas</label>
                    <textarea name="notes" rows="2" class="{{ $inputClass }}" placeholder="Transportista, observaciones...">{{ old('notes', $transfer?->notes) }}</textarea>
                </div>
            </div>

            <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
                <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                    <h2 class="font-extrabold text-slate-800 dark:text-white">Existencia en {{ $origen->name }}</h2>
                    <p class="text-xs text-slate-400">Indique cuánto sale de cada lote; las filas sin cantidad no se trasladan.</p>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4">Lote</th>
                                <th class="py-3 px-4">Ubicación origen</th>
                                <th class="py-3 px-4 text-right">Existencia</th>
                                <th class="py-3 px-4 w-36">Cantidad a trasladar</th>
                                <th class="py-3 px-4 w-48">Ubicación destino</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            @forelse($existencias as $i => $fila)
                                @php($actual = $actuales->get(\App\Services\InventoryTransferService::clave($fila->id_inventory_lot, $fila->id_location)))
                                <tr>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-800 dark:text-white block">{{ $fila->product?->name }}</span>
                                        <span class="text-xs font-mono text-slate-400">{{ $fila->product?->sku }}</span>
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs font-bold text-[#005e66] dark:text-teal-400">{{ $fila->lot?->lot_code }}</td>
                                    <td class="py-3 px-4 font-mono text-xs">{{ $fila->location?->code ?? 'Sin ubicación' }}</td>
                                    <td class="py-3 px-4 text-right font-mono font-bold">{{ $qty($fila->stock) }} <span class="text-xs font-sans text-slate-400">{{ $fila->product?->purchaseUnit?->name }}</span></td>
                                    <td class="py-3 px-4">
                                        <input type="hidden" name="details[{{ $i }}][id_inventory_lot]" value="{{ $fila->id_inventory_lot }}">
                                        <input type="hidden" name="details[{{ $i }}][id_source_location]" value="{{ $fila->id_location }}">
                                        <input type="number" step="0.0001" min="0" max="{{ (float) $fila->stock }}" name="details[{{ $i }}][quantity_dispatched]"
                                            value="{{ old("details.$i.quantity_dispatched", $actual ? (float) $actual->quantity_dispatched : '') }}"
                                            class="{{ $inputClass }} text-right font-mono @error("details.$i.quantity_dispatched") border-rose-400 @enderror">
                                        @error("details.$i.quantity_dispatched")
                                            <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                        @enderror
                                    </td>
                                    <td class="py-3 px-4">
                                        <select name="details[{{ $i }}][id_destination_location]" class="{{ $inputClass }}">
                                            <option value="">-- Al recibir --</option>
                                            @foreach($ubicacionesDestino as $ubicacion)
                                                <option value="{{ $ubicacion->id_location }}" @selected((string) old("details.$i.id_destination_location", $actual?->id_destination_location) === (string) $ubicacion->id_location)>{{ $ubicacion->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="py-12 text-center text-slate-400 text-sm">La bodega {{ $origen->name }} no tiene existencias para trasladar.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($existencias->isNotEmpty())
                <div class="flex justify-end gap-3">
                    <a href="{{ $transfer ? route('inventory-transfers.show', $transfer) : route('inventory-transfers.index') }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">Cancelar</a>
                    <button type="submit" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Guardar borrador</button>
                </div>
            @endif
        </form>
    @endif
</div>
@endsection
