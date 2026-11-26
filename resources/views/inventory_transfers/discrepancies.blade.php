@extends('layouts.app')
@section('title', 'Discrepancias '.$transfer->transfer_code)

@section('content')
@php
    use App\Models\InventoryTransferDiscrepancy as D;

    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $inputClass = 'w-full px-3 py-2 rounded-xl border border-line dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    // Lo ya registrado sin regularizar, para corregirlo
    $actual = fn ($detalle, $tipo) => (float) $detalle->discrepancies->whereNull('regularized_at')->where('discrepancy_type', $tipo)->sum(fn ($d) => (float) $d->quantity);
    $actualCampo = fn ($detalle, $campo) => $detalle->discrepancies->whereNull('regularized_at')->first()?->$campo;
    $ubicacionSobrante = fn ($detalle) => $detalle->discrepancies->whereNull('regularized_at')->firstWhere('discrepancy_type', D::TYPE_SURPLUS)?->id_destination_inventory_location;
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Traslados</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 font-mono">{{ $transfer->transfer_code }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-primary dark:text-accent dark:text-teal-400 tracking-tight mt-1">Registrar discrepancias</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5 max-w-3xl">
                Lo que sigue en el vehículo y no va a llegar se registra como <strong>faltante</strong> o <strong>daño</strong> (se da de baja);
                lo que llegó de más, como <strong>sobrante</strong> (sale del mismo lote en la bodega origen, de donde salió sin registrarse,
                y entra a la destino). Para cerrar el traslado, faltantes y daños
                deben cubrir todo lo que sigue en el vehículo; quien autoriza los traslados regulariza las existencias.
            </p>
        </div>
        <a href="{{ route('inventory-transfers.show', $transfer) }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver</a>
    </div>

    <form method="POST" action="{{ route('inventory-transfers.discrepancies.store', $transfer) }}" class="bg-surface dark:bg-slate-900 rounded-2xl border border-line dark:border-slate-800 shadow-xs overflow-hidden">
        @csrf
        @error('discrepancies')
            <p class="px-6 pt-4 text-rose-500 text-xs font-semibold">{{ $message }}</p>
        @enderror
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-line dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4 min-w-56">Producto / Lote</th>
                        <th class="py-3 px-4 text-right">Despachado</th>
                        <th class="py-3 px-4 text-right">Recibido</th>
                        <th class="py-3 px-4 text-right">En vehículo</th>
                        <th class="py-3 px-4 w-28">Faltante</th>
                        <th class="py-3 px-4 w-28">Daño</th>
                        <th class="py-3 px-4 w-28">Sobrante</th>
                        <th class="py-3 px-4 w-44">Ubicación del sobrante</th>
                        <th class="py-3 px-4">Notas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @foreach($transfer->details as $detalle)
                        @php
                            $id = $detalle->id_inventory_transfer_detail;
                            $pendiente = max(0, $detalle->pendingQuantity());
                            $campo = fn ($nombre, $tipo) => old("discrepancies.$id.$nombre", ($valor = $actual($detalle, $tipo)) > 0 ? $valor : '');
                        @endphp
                        <tr>
                            <td class="py-3 px-4">
                                <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                <span class="text-xs font-mono text-slate-400">{{ $detalle->lot?->lot_code }}</span>
                                @if($detalle->isUnexpected())
                                    <span class="block text-[11px] font-semibold text-amber-600 dark:text-amber-400">No venía en el traslado</span>
                                @endif
                            </td>
                            <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_dispatched) }}</td>
                            <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_received) }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold {{ $pendiente > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-slate-400' }}">{{ $qty($pendiente) }}</td>
                            <td class="py-3 px-4">
                                <input type="number" step="0.0001" min="0" max="{{ $pendiente }}" name="discrepancies[{{ $id }}][shortage]" value="{{ $campo('shortage', D::TYPE_SHORTAGE) }}" class="{{ $inputClass }} text-right font-mono @error("discrepancies.$id.shortage") border-rose-400 @enderror">
                                @error("discrepancies.$id.shortage")
                                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="0.0001" min="0" max="{{ $pendiente }}" name="discrepancies[{{ $id }}][damage]" value="{{ $campo('damage', D::TYPE_DAMAGE) }}" class="{{ $inputClass }} text-right font-mono">
                            </td>
                            <td class="py-3 px-4">
                                <input type="number" step="0.0001" min="0" name="discrepancies[{{ $id }}][surplus]" value="{{ $campo('surplus', D::TYPE_SURPLUS) }}" class="{{ $inputClass }} text-right font-mono">
                            </td>
                            <td class="py-3 px-4">
                                <select name="discrepancies[{{ $id }}][id_destination_inventory_location]" class="{{ $inputClass }} @error("discrepancies.$id.id_destination_inventory_location") border-rose-400 @enderror">
                                    <option value="">-- Solo si hay sobrante --</option>
                                    @foreach($ubicacionesDestino as $ubicacion)
                                        <option value="{{ $ubicacion->id_inventory_location }}" @selected((string) old("discrepancies.$id.id_destination_inventory_location", $ubicacionSobrante($detalle) ?? $detalle->id_destination_inventory_location) === (string) $ubicacion->id_inventory_location)>{{ $ubicacion->location?->code }}</option>
                                    @endforeach
                                </select>
                                @error("discrepancies.$id.id_destination_inventory_location")
                                    <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                @enderror
                            </td>
                            <td class="py-3 px-4">
                                <input type="text" maxlength="500" name="discrepancies[{{ $id }}][notes]" value="{{ old("discrepancies.$id.notes", $actualCampo($detalle, 'notes')) }}" placeholder="Qué pasó..." class="{{ $inputClass }}">
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Lo que llegó y no venía en el traslado -->
        <div class="px-6 py-5 border-t border-line dark:border-slate-800 space-y-3">
            <div>
                <h2 class="font-extrabold text-slate-800 dark:text-white">Llegó algo que no venía en el traslado</h2>
                <p class="text-xs text-slate-400">
                    Elija el lote por su viñeta. Queda como una línea más del traslado con su sobrante: al regularizar sale de
                    {{ $transfer->sourceWarehouse?->name }} y lo que ya no esté allí entra como ganancia.
                </p>
            </div>
            @if($lotesEnOrigen->isEmpty())
                <p class="text-sm text-slate-400">{{ $transfer->sourceWarehouse?->name }} no tiene otros lotes con existencia.</p>
            @else
                @for($i = 0; $i < 3; $i++)
                    <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-start">
                        <div class="md:col-span-5">
                            <select name="extras[{{ $i }}][id_inventory_lot]" class="{{ $inputClass }} @error("extras.$i.id_inventory_lot") border-rose-400 @enderror">
                                <option value="">-- Lote que llegó --</option>
                                @foreach($lotesEnOrigen as $lote)
                                    <option value="{{ $lote->id_inventory_lot }}" @selected((string) old("extras.$i.id_inventory_lot") === (string) $lote->id_inventory_lot)>
                                        {{ $lote->lot_code }} · {{ $lote->product }} (hay {{ $qty($lote->stock) }})
                                    </option>
                                @endforeach
                            </select>
                            @error("extras.$i.id_inventory_lot")
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <input type="number" step="0.0001" min="0" name="extras[{{ $i }}][quantity]" value="{{ old("extras.$i.quantity") }}" placeholder="Cantidad" class="{{ $inputClass }} text-right font-mono @error("extras.$i.quantity") border-rose-400 @enderror">
                            @error("extras.$i.quantity")
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-2">
                            <select name="extras[{{ $i }}][id_destination_inventory_location]" class="{{ $inputClass }} @error("extras.$i.id_destination_inventory_location") border-rose-400 @enderror">
                                <option value="">-- Entra en --</option>
                                @foreach($ubicacionesDestino as $ubicacion)
                                    <option value="{{ $ubicacion->id_inventory_location }}" @selected((string) old("extras.$i.id_destination_inventory_location") === (string) $ubicacion->id_inventory_location)>{{ $ubicacion->location?->code }}</option>
                                @endforeach
                            </select>
                            @error("extras.$i.id_destination_inventory_location")
                                <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="md:col-span-3">
                            <input type="text" maxlength="500" name="extras[{{ $i }}][notes]" value="{{ old("extras.$i.notes") }}" placeholder="Qué pasó..." class="{{ $inputClass }}">
                        </div>
                    </div>
                @endfor
            @endif
        </div>

        <div class="px-6 py-4 border-t border-line dark:border-slate-800 flex justify-end gap-3">
            <a href="{{ route('inventory-transfers.show', $transfer) }}" class="px-5 py-2.5 rounded-xl text-sm font-bold text-slate-600 dark:text-slate-300 border border-line dark:border-slate-700">Cancelar</a>
            <button type="submit" class="bg-rose-600 hover:bg-rose-700 text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Registrar discrepancias</button>
        </div>
    </form>
</div>
@endsection
