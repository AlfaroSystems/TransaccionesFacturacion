@extends('layouts.app')
@section('title', 'Traslado '.$transfer->transfer_code)

@section('content')
@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $inputClass = 'w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $statusClasses = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'dispatched' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        'received' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'cancelled' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
    ];
    $porRecibir = $transfer->status === \App\Models\InventoryTransfer::STATUS_DISPATCHED;
    $recibido = $transfer->status === \App\Models\InventoryTransfer::STATUS_RECEIVED;
    $valor = $transfer->details->sum(fn ($d) => (float) $d->quantity_dispatched * (float) $d->unit_cost);
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Traslados</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Detalle</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight font-mono">{{ $transfer->transfer_code }}</h1>
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold {{ $statusClasses[$transfer->status] ?? '' }}">{{ $transfer->statusLabel() }}</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Creado el {{ $transfer->created_at?->format('d/m/Y h:i A') }} por {{ $transfer->user?->username ?? 'Sistema' }}</p>
        </div>
        <div class="flex flex-wrap gap-2">
            @if($transfer->isDraft())
                @can('inventory.trasladar')
                    <a href="{{ route('inventory-transfers.edit', $transfer) }}" class="px-4 py-2 rounded-xl text-sm font-bold bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-300">Editar</a>
                    <form method="POST" action="{{ route('inventory-transfers.dispatch', $transfer) }}" onsubmit="return confirm('¿Despachar el traslado? Su mercadería saldrá de {{ addslashes($transfer->sourceWarehouse?->name) }}.')">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold bg-[#005e66] hover:bg-[#004d54] text-white">Despachar</button>
                    </form>
                    <form method="POST" action="{{ route('inventory-transfers.cancel', $transfer) }}" onsubmit="return confirm('¿Cancelar el traslado?')">
                        @csrf
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300">Cancelar</button>
                    </form>
                    <form method="POST" action="{{ route('inventory-transfers.destroy', $transfer) }}" onsubmit="return confirm('¿Eliminar el borrador?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-500 hover:text-rose-600">Eliminar</button>
                    </form>
                @endcan
            @endif
            <a href="{{ route('inventory-transfers.index') }}" class="px-4 py-2 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">← Volver</a>
        </div>
    </div>

    <!-- Datos generales -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-2 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Origen</h3>
            <p class="font-bold text-slate-800 dark:text-white">{{ $transfer->sourceWarehouse?->name }}</p>
            <p class="text-xs text-slate-500">{{ $transfer->sourceWarehouse?->branch?->name }}</p>
            <p class="text-xs text-slate-500">Despachado: <strong>{{ $transfer->dispatched_at?->format('d/m/Y h:i A') ?? 'Pendiente' }}</strong></p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-2 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Destino</h3>
            <p class="font-bold text-slate-800 dark:text-white">{{ $transfer->destinationWarehouse?->name }}</p>
            <p class="text-xs text-slate-500">{{ $transfer->destinationWarehouse?->branch?->name }}</p>
            <p class="text-xs text-slate-500">Recibido: <strong>{{ $transfer->received_at?->format('d/m/Y h:i A') ?? 'Pendiente' }}</strong></p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-2 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Traslado</h3>
            <p class="text-xs text-slate-500">Fecha: <strong class="text-slate-800 dark:text-white">{{ $transfer->transfer_date?->format('d/m/Y') }}</strong></p>
            <p class="text-xs text-slate-500">Vehículo / tránsito: <strong class="text-slate-800 dark:text-white">{{ $transfer->vehicleLocation ? $transfer->vehicleLocation->code.' ('.$transfer->vehicleLocation->warehouse?->name.')' : 'Sin indicar' }}</strong></p>
            <p class="text-xs text-slate-500">Valor despachado: <strong class="text-[#005e66] dark:text-teal-400">${{ number_format($valor, 2) }}</strong></p>
            @if($transfer->notes)
                <p class="text-xs text-slate-500 pt-1">{{ $transfer->notes }}</p>
            @endif
        </div>
    </div>

    <!-- Líneas (y recepción, si está despachado) -->
    <form method="POST" action="{{ route('inventory-transfers.receive', $transfer) }}">
        @csrf
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Lotes trasladados</h2>
                @if($porRecibir)
                    <span class="text-xs text-slate-400">Indique lo que llegó; lo que falte se registra como merma.</span>
                @endif
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Lote</th>
                            <th class="py-3 px-4">Ubicación origen</th>
                            <th class="py-3 px-4 text-right">Despachado</th>
                            <th class="py-3 px-4 {{ $porRecibir ? 'w-36' : 'text-right' }}">Recibido</th>
                            <th class="py-3 px-4 {{ $porRecibir ? 'w-48' : '' }}">Ubicación destino</th>
                            <th class="py-3 px-4 text-right">Costo unit.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($transfer->details as $detalle)
                            @php($id = $detalle->id_inventory_transfer_detail)
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $detalle->product?->sku }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-bold text-[#005e66] dark:text-teal-400">{{ $detalle->lot?->lot_code }}</td>
                                <td class="py-3 px-4 font-mono text-xs">{{ $detalle->sourceLocation?->code ?? 'Sin ubicación' }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold">{{ $qty($detalle->quantity_dispatched) }} <span class="text-xs font-sans text-slate-400">{{ $detalle->product?->purchaseUnit?->name }}</span></td>
                                @if($porRecibir)
                                    <td class="py-3 px-4">
                                        <input type="number" step="0.0001" min="0" max="{{ (float) $detalle->quantity_dispatched }}" name="received[{{ $id }}][quantity_received]"
                                            value="{{ old("received.$id.quantity_received", (float) $detalle->quantity_dispatched) }}" class="{{ $inputClass }} text-right font-mono">
                                    </td>
                                    <td class="py-3 px-4">
                                        <select name="received[{{ $id }}][id_destination_location]" class="{{ $inputClass }}">
                                            <option value="">-- Sin ubicación --</option>
                                            @foreach($ubicacionesDestino as $ubicacion)
                                                <option value="{{ $ubicacion->id_location }}" @selected((string) old("received.$id.id_destination_location", $detalle->id_destination_location) === (string) $ubicacion->id_location)>{{ $ubicacion->code }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                @else
                                    <td class="py-3 px-4 text-right font-mono">
                                        @if($recibido)
                                            <span class="font-bold">{{ $qty($detalle->quantity_received) }}</span>
                                            @if((float) $detalle->quantity_received < (float) $detalle->quantity_dispatched)
                                                <span class="block text-[11px] font-sans font-bold text-rose-500">Merma: {{ $qty((float) $detalle->quantity_dispatched - (float) $detalle->quantity_received) }}</span>
                                            @endif
                                        @else
                                            <span class="text-slate-400">—</span>
                                        @endif
                                    </td>
                                    <td class="py-3 px-4 font-mono text-xs">{{ $detalle->destinationLocation?->code ?? ($recibido ? 'Sin ubicación' : 'Al recibir') }}</td>
                                @endif
                                <td class="py-3 px-4 text-right font-mono">${{ number_format((float) $detalle->unit_cost, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        @if($porRecibir)
            @can('inventory.recibir_traslados')
                <div class="flex justify-end mt-4">
                    <button type="submit" onclick="return confirm('¿Confirmar la recepción? Lo recibido entrará a {{ addslashes($transfer->destinationWarehouse?->name) }}.')" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Confirmar recepción</button>
                </div>
            @endcan
        @endif
    </form>
</div>
@endsection
