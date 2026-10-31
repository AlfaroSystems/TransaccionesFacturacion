@extends('layouts.app')
@section('title', 'Lote '.$lot->lot_code)

@section('content')
@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $inputClass = 'w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $unidad = $lot->purchaseDetail?->unit?->name ?? $lot->product?->purchaseUnit?->name;
    $statusClass = match ($lot->status) {
        'active' => 'bg-teal-100 text-[#005e66] border-teal-300 dark:bg-teal-950 dark:text-teal-300 dark:border-teal-800',
        'pending_placement' => 'bg-amber-100 text-amber-800 border-amber-300 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
        default => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800',
    };
    $compra = $lot->purchaseDetail?->purchase;
    $asignado = (float) $lot->placements->sum('quantity');

    // Pasos del flujo: retaceo aplicado → viñetas → ubicación → colocación confirmada
    $pasos = [
        ['Retaceo aplicado', true],
        ['Viñetas impresas', $lot->labelPrints->isNotEmpty()],
        ['Ubicación asignada', $lot->status === 'active' || abs($asignado - (float) $lot->quantity_received) < 0.00001],
        ['Colocación confirmada', $lot->status === 'active'],
    ];

    // Filas del reparto: lo que se acaba de enviar, lo guardado o, la primera vez, todo el lote
    // en la última ubicación del producto
    $filas = old('placements')
        ?? ($lot->placements->isNotEmpty()
            ? $lot->placements->map(fn ($p) => ['id_location' => $p->id_location, 'quantity' => (float) $p->quantity])->all()
            : [['id_location' => $sugerida, 'quantity' => (float) $lot->quantity_received]]);

    $opcionUbicacion = function ($location) use ($ocupado, $qty) {
        $guarda = (float) ($ocupado[$location->id_location] ?? 0);

        return $location->code.((int) $location->capacity > 0
            ? ' · guarda '.$qty($guarda).' de '.$location->capacity
            : ' · sin límite');
    };
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <a href="{{ route('inventory.lots.index') }}" class="text-xs font-semibold text-slate-500 dark:text-slate-400 hover:underline">Lotes</a>
            </div>
            <div class="flex flex-wrap items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight font-mono">{{ $lot->lot_code }}</h1>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $statusClass }}">● {{ $lot->statusLabel() }}</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $lot->product?->name }} · <span class="font-mono">{{ $lot->product?->sku }}</span></p>
        </div>
        <div class="flex flex-wrap items-center gap-3">
            @if($lot->status !== 'cancelled')
                @can('inventory.etiquetas')
                    <a href="{{ route('inventory.labels', $lot) }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs transition-all shadow-md">🏷️ Viñetas</a>
                @endcan
            @endif
            <a href="{{ route('inventory.lots.index') }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver a lotes</a>
        </div>
    </div>

    <!-- Datos del lote -->
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Cantidad</span>
            <span class="text-xl font-extrabold text-slate-800 dark:text-white font-mono">{{ $qty($lot->quantity_received) }}</span>
            <span class="text-xs font-semibold text-slate-400">{{ $unidad }}</span>
            @if($lot->supplier_lot_number || $lot->expiration_date)
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 block mt-1">
                    {{ $lot->supplier_lot_number ? 'Lote prov. '.$lot->supplier_lot_number : '' }}{{ $lot->supplier_lot_number && $lot->expiration_date ? ' · ' : '' }}{{ $lot->expiration_date ? 'Vence '.$lot->expiration_date->format('d/m/Y') : '' }}
                </span>
            @endif
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Costo unitario</span>
            <span class="text-xl font-extrabold text-slate-800 dark:text-white font-mono">${{ number_format((float) $lot->unit_cost, 4) }}</span>
            <span class="text-xs font-semibold text-slate-400 block">Total ${{ number_format((float) $lot->total_cost, 2) }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Bodega</span>
            <span class="text-sm font-extrabold text-slate-800 dark:text-white block">{{ $lot->warehouseAllBranches?->name ?? 'N/A' }}</span>
            <span class="text-xs font-semibold text-slate-400">Recibido el {{ $lot->received_at?->format('d/m/Y') }}</span>
        </div>
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs">
            <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Compra</span>
            @if($compra)
                <a href="{{ route('purchases.show', $compra) }}" class="text-sm font-extrabold font-mono text-[#005e66] dark:text-teal-400 hover:underline block">{{ $compra->purchase_code }}</a>
            @else
                <span class="text-sm font-extrabold text-slate-400 block">—</span>
            @endif
            <span class="text-xs font-semibold text-slate-400">{{ $lot->labelPrints->sum('quantity_printed') }} viñeta(s) impresa(s)</span>
        </div>
    </div>

    <!-- Avance del flujo -->
    @if($lot->status !== 'cancelled')
        <ol class="grid grid-cols-2 md:grid-cols-4 gap-3">
            @foreach($pasos as $i => [$paso, $hecho])
                <li class="flex items-center gap-2 px-4 py-3 rounded-xl border text-xs font-bold {{ $hecho ? 'bg-teal-50 border-teal-200 text-[#005e66] dark:bg-teal-950/40 dark:border-teal-800 dark:text-teal-300' : 'bg-white border-slate-200 text-slate-400 dark:bg-slate-900 dark:border-slate-700' }}">
                    <span class="w-6 h-6 rounded-full flex items-center justify-center {{ $hecho ? 'bg-[#005e66] text-white' : 'bg-slate-100 dark:bg-slate-800' }}">{{ $hecho ? '✓' : $i + 1 }}</span>
                    {{ $paso }}
                </li>
            @endforeach
        </ol>
    @endif

    @if($lot->isPending())
        @can('inventory.ubicar')
            @php
                // Partes de la división: las que se acaban de enviar o, de entrada, todo el lote y una vacía
                $partes = old('parts') ?? [
                    ['quantity' => (float) $lot->quantity_received, 'supplier_lot_number' => $lot->supplier_lot_number, 'expiration_date' => $lot->expiration_date?->format('Y-m-d')],
                    ['quantity' => '', 'supplier_lot_number' => '', 'expiration_date' => ''],
                ];
            @endphp
            <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
                <!-- Datos del proveedor -->
                <form method="POST" action="{{ route('inventory.lots.update', $lot) }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-4">
                    @csrf
                    @method('PUT')
                    <div>
                        <h2 class="font-extrabold text-slate-800 dark:text-white">Datos del proveedor</h2>
                        <p class="text-xs text-slate-400">El lote del fabricante y su vencimiento, si los trae.</p>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Lote del proveedor</label>
                        <input type="text" name="supplier_lot_number" maxlength="100" value="{{ old('supplier_lot_number', $lot->supplier_lot_number) }}" placeholder="Ej.: B2026-0412" class="{{ $inputClass }}">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Fecha de vencimiento</label>
                        <input type="date" name="expiration_date" value="{{ old('expiration_date', $lot->expiration_date?->format('Y-m-d')) }}" class="{{ $inputClass }}">
                    </div>
                    <button type="submit" class="w-full px-5 py-2.5 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800">Guardar datos</button>
                </form>

                <!-- Dividir el lote -->
                <form method="POST" action="{{ route('inventory.lots.split', $lot) }}" class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
                    @csrf
                    <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                        <h2 class="font-extrabold text-slate-800 dark:text-white">Dividir el lote</h2>
                        <p class="text-xs text-slate-400">Si llegó en tandas o con lotes distintos del fabricante. La primera parte se queda con {{ $lot->lot_code }}; cada otra es un lote nuevo con el mismo costo. Deben sumar {{ $qty($lot->quantity_received) }}.</p>
                    </div>
                    <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                            <tr>
                                <th class="py-3 px-4">Parte</th>
                                <th class="py-3 px-4 w-32 text-right">Cantidad</th>
                                <th class="py-3 px-4">Lote del proveedor</th>
                                <th class="py-3 px-4 w-40">Vence</th>
                                <th class="py-3 px-4 w-12"></th>
                            </tr>
                        </thead>
                        <tbody id="partesBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                            @foreach($partes as $i => $parte)
                                <tr class="parte-fila">
                                    <td class="py-3 px-4 text-xs font-bold whitespace-nowrap">{{ $loop->first ? $lot->lot_code : 'Lote nuevo' }}</td>
                                    <td class="py-3 px-4">
                                        <input type="number" step="0.0001" min="0" name="parts[{{ $i }}][quantity]" value="{{ $parte['quantity'] ?? '' }}" required class="{{ $inputClass }} text-right font-mono parte-cantidad @error("parts.$i.quantity") border-rose-400 @enderror">
                                    </td>
                                    <td class="py-3 px-4"><input type="text" name="parts[{{ $i }}][supplier_lot_number]" maxlength="100" value="{{ $parte['supplier_lot_number'] ?? '' }}" class="{{ $inputClass }}"></td>
                                    <td class="py-3 px-4"><input type="date" name="parts[{{ $i }}][expiration_date]" value="{{ $parte['expiration_date'] ?? '' }}" class="{{ $inputClass }}"></td>
                                    <td class="py-3 px-4 text-right">
                                        @unless($loop->first)
                                            <button type="button" class="parte-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar parte">✕</button>
                                        @endunless
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                    <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                        <div class="flex items-center gap-4">
                            <button type="button" id="parteAgregar" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">+ Agregar parte</button>
                            <span class="text-xs font-semibold text-slate-500">Suma: <span id="partesTotal" class="font-mono font-extrabold">0</span> de <span class="font-mono font-extrabold">{{ $qty($lot->quantity_received) }}</span></span>
                        </div>
                        <button type="submit" class="bg-slate-800 hover:bg-slate-700 text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Dividir lote</button>
                    </div>

                    <template id="partePlantilla">
                        <tr class="parte-fila">
                            <td class="py-3 px-4 text-xs font-bold whitespace-nowrap">Lote nuevo</td>
                            <td class="py-3 px-4"><input type="number" step="0.0001" min="0" name="parts[__i__][quantity]" required class="{{ $inputClass }} text-right font-mono parte-cantidad"></td>
                            <td class="py-3 px-4"><input type="text" name="parts[__i__][supplier_lot_number]" maxlength="100" class="{{ $inputClass }}"></td>
                            <td class="py-3 px-4"><input type="date" name="parts[__i__][expiration_date]" class="{{ $inputClass }}"></td>
                            <td class="py-3 px-4 text-right"><button type="button" class="parte-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar parte">✕</button></td>
                        </tr>
                    </template>
                </form>
            </div>
        @endcan

        <!-- Reparto en ubicaciones -->
        <form method="POST" action="{{ route('inventory.lots.placements', $lot) }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden" id="repartoForm">
            @csrf
            @method('PUT')
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Ubicación en {{ $lot->warehouseAllBranches?->name }}</h2>
                <p class="text-xs text-slate-400">Reparta el lote en una o varias ubicaciones. Puede guardarlo y corregirlo; al confirmar la colocación entra al inventario y ya no se edita.</p>
            </div>
            @if($ubicaciones->isEmpty())
                <p class="px-6 py-10 text-center text-sm text-slate-400">La bodega no tiene ubicaciones activas: créelas en Ubicaciones para poder colocar el lote.</p>
            @else
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Ubicación</th>
                            <th class="py-3 px-4 w-44 text-right">Cantidad ({{ $unidad }})</th>
                            <th class="py-3 px-4 w-16"></th>
                        </tr>
                    </thead>
                    <tbody id="repartoBody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($filas as $i => $fila)
                            <tr class="reparto-fila">
                                <td class="py-3 px-4">
                                    <select name="placements[{{ $i }}][id_location]" class="{{ $inputClass }} @error("placements.$i.id_location") border-rose-400 @enderror">
                                        <option value="">-- Elija la ubicación --</option>
                                        @foreach($ubicaciones as $ubicacion)
                                            <option value="{{ $ubicacion->id_location }}" @selected((string) ($fila['id_location'] ?? '') === (string) $ubicacion->id_location)>{{ $opcionUbicacion($ubicacion) }}</option>
                                        @endforeach
                                    </select>
                                    @error("placements.$i.id_location")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-3 px-4">
                                    <input type="number" step="0.0001" min="0" name="placements[{{ $i }}][quantity]" value="{{ $fila['quantity'] ?? '' }}" class="{{ $inputClass }} text-right font-mono reparto-cantidad @error("placements.$i.quantity") border-rose-400 @enderror">
                                    @error("placements.$i.quantity")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-3 px-4 text-right">
                                    <button type="button" class="reparto-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar fila">✕</button>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-center md:justify-between gap-3">
                    <div class="flex items-center gap-4">
                        <button type="button" id="repartoAgregar" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">+ Agregar ubicación</button>
                        <span class="text-xs font-semibold text-slate-500">Asignado: <span id="repartoTotal" class="font-mono font-extrabold">{{ $qty($asignado) }}</span> de <span class="font-mono font-extrabold">{{ $qty($lot->quantity_received) }}</span></span>
                    </div>
                    @can('inventory.ubicar')
                        <div class="flex gap-3">
                            <button type="submit" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-700 dark:text-slate-200 border border-slate-200 dark:border-slate-700 hover:bg-slate-50 dark:hover:bg-slate-800">Guardar reparto</button>
                            <button type="submit" name="confirmar" value="1" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Confirmar colocación</button>
                        </div>
                    @endcan
                </div>

                <template id="repartoPlantilla">
                    <tr class="reparto-fila">
                        <td class="py-3 px-4">
                            <select name="placements[__i__][id_location]" class="{{ $inputClass }}">
                                <option value="">-- Elija la ubicación --</option>
                                @foreach($ubicaciones as $ubicacion)
                                    <option value="{{ $ubicacion->id_location }}">{{ $opcionUbicacion($ubicacion) }}</option>
                                @endforeach
                            </select>
                        </td>
                        <td class="py-3 px-4">
                            <input type="number" step="0.0001" min="0" name="placements[__i__][quantity]" class="{{ $inputClass }} text-right font-mono reparto-cantidad">
                        </td>
                        <td class="py-3 px-4 text-right">
                            <button type="button" class="reparto-quitar text-slate-400 hover:text-rose-500 font-bold" title="Quitar fila">✕</button>
                        </td>
                    </tr>
                </template>
            @endif
        </form>
    @endif

    @if($lot->status === 'active')
        <!-- Dónde está y cambio de ubicación -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Existencia por ubicación</h2>
                <p class="text-xs text-slate-400">Para moverlo dentro de la bodega, registre un cambio de ubicación: queda en el kardex.</p>
            </div>
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Bodega / Ubicación</th>
                        <th class="py-3 px-4 text-right">Existencia</th>
                        @can('inventory.ubicar')
                            <th class="py-3 px-4">Cambiar de ubicación</th>
                        @endcan
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($existencias as $fila)
                        <tr>
                            <td class="py-3 px-4">
                                <span class="font-semibold text-slate-700 dark:text-slate-200 block">{{ $fila->warehouse?->name }}</span>
                                <span class="text-xs font-mono text-slate-400">{{ $fila->location?->code ?? 'Sin ubicación' }}</span>
                            </td>
                            <td class="py-3 px-4 text-right font-mono font-extrabold">{{ $qty($fila->stock) }} <span class="text-xs font-sans text-slate-400">{{ $unidad }}</span></td>
                            @can('inventory.ubicar')
                                <td class="py-3 px-4">
                                    <form method="POST" action="{{ route('inventory.lots.relocate', $lot) }}" class="flex flex-wrap items-center gap-2">
                                        @csrf
                                        <input type="hidden" name="id_warehouse" value="{{ $fila->id_warehouse }}">
                                        <input type="hidden" name="id_source_location" value="{{ $fila->id_location }}">
                                        <select name="id_destination_location" required class="{{ $inputClass }} md:w-44">
                                            <option value="">-- Hacia --</option>
                                            @foreach(($destinos[$fila->id_warehouse] ?? collect())->where('id_location', '!=', $fila->id_location) as $destino)
                                                <option value="{{ $destino->id_location }}">{{ $destino->code }}</option>
                                            @endforeach
                                        </select>
                                        <input type="number" step="0.0001" min="0.0001" max="{{ (float) $fila->stock }}" name="quantity" required value="{{ (float) $fila->stock }}" class="{{ $inputClass }} md:w-28 text-right font-mono">
                                        <button type="submit" class="px-4 py-2 rounded-xl text-xs font-bold bg-slate-800 hover:bg-slate-700 text-white">Mover</button>
                                    </form>
                                </td>
                            @endcan
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="py-10 text-center text-slate-400 text-sm">No queda existencia de este lote en sus bodegas.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    @endif

    <!-- Movimientos del lote -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
            <h2 class="font-extrabold text-slate-800 dark:text-white">Movimientos del lote</h2>
            <a href="{{ route('inventory.movements', ['search' => $lot->lot_code]) }}" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">Ver en el kardex</a>
        </div>
        <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
            <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                <tr>
                    <th class="py-3 px-4">Fecha</th>
                    <th class="py-3 px-4">Tipo</th>
                    <th class="py-3 px-4">Bodega / Ubicación</th>
                    <th class="py-3 px-4 text-right">Entrada</th>
                    <th class="py-3 px-4 text-right">Salida</th>
                    <th class="py-3 px-4">Usuario</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                @forelse($movimientos as $movimiento)
                    <tr>
                        <td class="py-3 px-4 text-xs whitespace-nowrap">{{ $movimiento->movement_date->format('d/m/Y h:i A') }}</td>
                        <td class="py-3 px-4 text-xs font-bold">{{ $movimiento->typeLabel() }}</td>
                        <td class="py-3 px-4 text-xs">{{ $movimiento->warehouse?->name }} · <span class="font-mono">{{ $movimiento->location?->code ?? 'Sin ubicación' }}</span></td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ (float) $movimiento->quantity_in > 0 ? $qty($movimiento->quantity_in) : '' }}</td>
                        <td class="py-3 px-4 text-right font-mono font-bold text-rose-600 dark:text-rose-400">{{ (float) $movimiento->quantity_out > 0 ? $qty($movimiento->quantity_out) : '' }}</td>
                        <td class="py-3 px-4 text-xs">{{ $movimiento->user?->username ?? 'Sistema' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="6" class="py-10 text-center text-slate-400 text-sm">{{ $lot->isPending() ? 'Sin movimientos: entra al inventario al confirmar su colocación.' : 'Sin movimientos.' }}</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($lot->isPending() && $ubicaciones->isNotEmpty())
<script>
    (function () {
        const body = document.getElementById('repartoBody');
        const plantilla = document.getElementById('repartoPlantilla');
        const total = document.getElementById('repartoTotal');
        let siguiente = {{ count($filas) ? max(array_keys($filas)) + 1 : 0 }};

        function sumar() {
            let suma = 0;
            body.querySelectorAll('.reparto-cantidad').forEach(input => { suma += parseFloat(input.value) || 0; });
            total.textContent = parseFloat(suma.toFixed(4)).toString();
        }

        document.getElementById('repartoAgregar').addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__i__', siguiente++));
        });

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('reparto-quitar')) {
                e.target.closest('tr').remove();
                sumar();
            }
        });

        body.addEventListener('input', sumar);
        sumar();
    })();
</script>
@endif

@if($lot->isPending())
@can('inventory.ubicar')
<script>
    // Partes de la división: agregar, quitar y sumar
    (function () {
        const body = document.getElementById('partesBody');
        const plantilla = document.getElementById('partePlantilla');
        const total = document.getElementById('partesTotal');
        let siguiente = {{ max(array_keys($partes)) + 1 }};

        function sumar() {
            let suma = 0;
            body.querySelectorAll('.parte-cantidad').forEach(input => { suma += parseFloat(input.value) || 0; });
            total.textContent = parseFloat(suma.toFixed(4)).toString();
        }

        document.getElementById('parteAgregar').addEventListener('click', function () {
            body.insertAdjacentHTML('beforeend', plantilla.innerHTML.replaceAll('__i__', siguiente++));
        });

        body.addEventListener('click', function (e) {
            if (e.target.classList.contains('parte-quitar')) {
                e.target.closest('tr').remove();
                sumar();
            }
        });

        body.addEventListener('input', sumar);
        sumar();
    })();
</script>
@endcan
@endif
@endsection
