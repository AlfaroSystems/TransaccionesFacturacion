@extends('layouts.app')
@section('title', 'Traslado '.$transfer->transfer_code)

@section('content')
@php
    use App\Models\InventoryTransfer as T;

    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $inputClass = 'w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $btn = 'px-4 py-2 rounded-xl text-sm font-bold transition-all';
    $statusClasses = [
        T::STATUS_DRAFT => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        T::STATUS_PENDING_AUTHORIZATION => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        T::STATUS_AUTHORIZED => 'bg-sky-50 text-sky-700 dark:bg-sky-950/60 dark:text-sky-300',
        T::STATUS_PREPARING => 'bg-indigo-50 text-indigo-700 dark:bg-indigo-950/60 dark:text-indigo-300',
        T::STATUS_IN_TRANSIT => 'bg-orange-50 text-orange-700 dark:bg-orange-950/60 dark:text-orange-300',
        T::STATUS_PARTIALLY_RECEIVED => 'bg-yellow-50 text-yellow-700 dark:bg-yellow-950/60 dark:text-yellow-300',
        T::STATUS_WITH_DISCREPANCY => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
        T::STATUS_RECEIVED => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        T::STATUS_CANCELLED => 'bg-slate-200 text-slate-500 dark:bg-slate-800 dark:text-slate-400',
    ];

    // Pasos del flujo y en cuál va el traslado
    $pasos = ['Crear', 'Autorizar', 'Preparar', 'En tránsito', 'Recepción', 'Finalizado'];
    $pasoActual = [
        T::STATUS_DRAFT => 0, T::STATUS_PENDING_AUTHORIZATION => 1, T::STATUS_AUTHORIZED => 2, T::STATUS_PREPARING => 2,
        T::STATUS_IN_TRANSIT => 3, T::STATUS_PARTIALLY_RECEIVED => 4, T::STATUS_WITH_DISCREPANCY => 4, T::STATUS_RECEIVED => 5,
    ][$transfer->status] ?? null;

    $estado = $transfer->status;
    $antesDeDespachar = in_array($estado, [T::STATUS_DRAFT, T::STATUS_PENDING_AUTHORIZATION, T::STATUS_AUTHORIZED, T::STATUS_PREPARING, T::STATUS_CANCELLED], true);
    $valor = $transfer->details->sum(fn ($d) => (float) ($antesDeDespachar ? $d->quantity_requested : $d->quantity_dispatched) * (float) $d->unit_cost);
    $enVehiculo = $transfer->details->sum(fn ($d) => max(0, $d->pendingQuantity()));
    $discrepancias = $transfer->details->flatMap(fn ($d) => $d->discrepancies->map(fn ($x) => [$d, $x]));
    $porRegularizar = $discrepancias->contains(fn ($par) => $par[1]->regularized_at === null);
    $unidad = fn ($d) => $d->product?->purchaseUnit?->name;
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado y acciones del paso actual -->
    <div class="flex flex-col lg:flex-row lg:items-start lg:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Traslados</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Detalle</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight font-mono">{{ $transfer->transfer_code }}</h1>
                <span class="px-2.5 py-1 rounded-lg text-xs font-extrabold {{ $statusClasses[$estado] ?? '' }}">{{ $transfer->statusLabel() }}</span>
            </div>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">Creado el {{ $transfer->created_at?->format('d/m/Y h:i A') }} por {{ $transfer->user?->username ?? 'Sistema' }}</p>
        </div>
        <div class="flex flex-wrap gap-2 lg:justify-end">
            @if($estado === T::STATUS_DRAFT)
                @can('inventory.trasladar')
                    <a href="{{ route('inventory-transfers.edit', $transfer) }}" class="{{ $btn }} bg-amber-50 text-amber-700 hover:bg-amber-100 dark:bg-amber-950/50 dark:text-amber-300">Editar</a>
                    <form method="POST" action="{{ route('inventory-transfers.submit', $transfer) }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-[#005e66] hover:bg-[#004d54] text-white">Enviar a autorizar</button>
                    </form>
                @endcan
            @endif

            @if($estado === T::STATUS_PENDING_AUTHORIZATION)
                @can('inventory.autorizar_traslados')
                    <form method="POST" action="{{ route('inventory-transfers.authorize', $transfer) }}" onsubmit="return confirm('¿Autorizar el traslado?')">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-emerald-600 hover:bg-emerald-700 text-white">Autorizar</button>
                    </form>
                    <button type="button" onclick="document.getElementById('rechazo').classList.toggle('hidden')" class="{{ $btn }} bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300">Rechazar</button>
                @endcan
            @endif

            @if($estado === T::STATUS_AUTHORIZED)
                @can('inventory.trasladar')
                    <form method="POST" action="{{ route('inventory-transfers.prepare', $transfer) }}">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-[#005e66] hover:bg-[#004d54] text-white">Preparar despacho</button>
                    </form>
                @endcan
            @endif

            @if(in_array($estado, [T::STATUS_AUTHORIZED, T::STATUS_PREPARING], true))
                @can('inventory.trasladar')
                    <a href="{{ route('inventory-transfers.picking', $transfer) }}" target="_blank" class="{{ $btn }} border border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-200 hover:bg-slate-50 dark:hover:bg-slate-800">Lista de preparación</a>
                @endcan
            @endif

            @if($estado === T::STATUS_PARTIALLY_RECEIVED)
                @can('inventory.recibir_traslados')
                    <form method="POST" action="{{ route('inventory-transfers.continue-transit', $transfer) }}" onsubmit="return confirm('¿Lo que falta llega después? El traslado vuelve a En tránsito.')">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-orange-50 text-orange-700 hover:bg-orange-100 dark:bg-orange-950/50 dark:text-orange-300">Sigue en tránsito</button>
                    </form>
                @endcan
            @endif

            @if($transfer->isReceivable())
                @can('inventory.recibir_traslados')
                    <a href="{{ route('inventory-transfers.discrepancies', $transfer) }}" class="{{ $btn }} bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300">{{ $porRegularizar ? 'Corregir discrepancias' : 'Registrar discrepancias' }}</a>
                @endcan
            @endif

            @if($estado === T::STATUS_WITH_DISCREPANCY)
                @can('inventory.regularizar_traslados')
                    <form method="POST" action="{{ route('inventory-transfers.regularize', $transfer) }}" onsubmit="return confirm('¿Regularizar las existencias? Faltantes y daños salen del vehículo como pérdida y los sobrantes entran a la bodega destino. El traslado se cierra.')">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-emerald-600 hover:bg-emerald-700 text-white">Regularizar existencias</button>
                    </form>
                @endcan
            @endif

            @if($transfer->isCancellable())
                @can('inventory.trasladar')
                    <form method="POST" action="{{ route('inventory-transfers.cancel', $transfer) }}" onsubmit="return confirm('¿Cancelar el traslado?')">
                        @csrf
                        <button type="submit" class="{{ $btn }} bg-rose-50 text-rose-700 hover:bg-rose-100 dark:bg-rose-950/50 dark:text-rose-300">Cancelar</button>
                    </form>
                @endcan
            @endif

            @if($estado === T::STATUS_DRAFT)
                @can('inventory.trasladar')
                    <form method="POST" action="{{ route('inventory-transfers.destroy', $transfer) }}" onsubmit="return confirm('¿Eliminar el borrador?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="{{ $btn }} text-slate-500 hover:text-rose-600">Eliminar</button>
                    </form>
                @endcan
            @endif

            <a href="{{ route('inventory-transfers.index') }}" class="{{ $btn }} text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700">← Volver</a>
        </div>
    </div>

    <!-- Rechazo: el motivo vuelve con el borrador -->
    @if($estado === T::STATUS_PENDING_AUTHORIZATION)
        @can('inventory.autorizar_traslados')
            <form id="rechazo" method="POST" action="{{ route('inventory-transfers.reject', $transfer) }}" class="{{ $errors->has('status_reason') ? '' : 'hidden' }} bg-white dark:bg-slate-900 rounded-2xl border border-rose-200 dark:border-rose-900 shadow-xs p-5 space-y-3">
                @csrf
                <label class="block text-xs font-bold text-slate-400 uppercase">Motivo del rechazo *</label>
                <textarea name="status_reason" rows="2" required maxlength="500" class="{{ $inputClass }} @error('status_reason') border-rose-400 @enderror" placeholder="Qué debe corregirse antes de volver a enviarlo...">{{ old('status_reason') }}</textarea>
                @error('status_reason')
                    <p class="text-rose-500 text-[11px] font-semibold">{{ $message }}</p>
                @enderror
                <div class="flex justify-end">
                    <button type="submit" class="{{ $btn }} bg-rose-600 hover:bg-rose-700 text-white">Rechazar y devolver a borrador</button>
                </div>
            </form>
        @endcan
    @endif

    @if($estado === T::STATUS_DRAFT && $transfer->status_reason)
        <div class="rounded-2xl border border-rose-200 dark:border-rose-900 bg-rose-50 dark:bg-rose-950/40 p-4 text-sm">
            <p class="font-extrabold text-rose-700 dark:text-rose-300">Rechazado: corríjalo y vuelva a enviarlo a autorizar</p>
            <p class="text-rose-700/80 dark:text-rose-300/80 mt-1">{{ $transfer->status_reason }}</p>
        </div>
    @endif

    <!-- Avance del flujo -->
    @if($pasoActual === null)
        <div class="rounded-2xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-800/50 p-4 text-sm font-bold text-slate-500">Traslado cancelado antes de despacharse: no movió inventario.</div>
    @else
        <ol class="grid grid-cols-3 md:grid-cols-6 gap-2">
            @foreach($pasos as $i => $paso)
                @php $hecho = $i < $pasoActual || $estado === T::STATUS_RECEIVED; @endphp
                <li class="rounded-xl px-3 py-2 text-xs font-bold border {{ $hecho ? 'bg-emerald-50 border-emerald-200 text-emerald-700 dark:bg-emerald-950/40 dark:border-emerald-900 dark:text-emerald-300' : ($i === $pasoActual ? 'bg-[#005e66] border-[#005e66] text-white' : 'bg-white border-slate-200 text-slate-400 dark:bg-slate-900 dark:border-slate-800') }}">
                    <span class="font-mono mr-1">{{ $i + 1 }}</span> {{ $paso }}
                </li>
            @endforeach
        </ol>
    @endif

    <!-- Datos generales -->
    <div class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-1.5 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Origen</h3>
            <p class="font-bold text-slate-800 dark:text-white">{{ $transfer->sourceWarehouse?->name }}</p>
            <p class="text-xs text-slate-500">{{ $transfer->sourceWarehouse?->branch?->name }}</p>
            <p class="text-xs text-slate-500">Despachado: <strong>{{ $transfer->dispatched_at?->format('d/m/Y h:i A') ?? 'Pendiente' }}</strong></p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-1.5 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Destino</h3>
            <p class="font-bold text-slate-800 dark:text-white">{{ $transfer->destinationWarehouse?->name }}</p>
            <p class="text-xs text-slate-500">{{ $transfer->destinationWarehouse?->branch?->name }}</p>
            <p class="text-xs text-slate-500">Recibido: <strong>{{ $transfer->received_at?->format('d/m/Y h:i A') ?? 'Pendiente' }}</strong></p>
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-1.5 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Transporte</h3>
            <p class="text-xs text-slate-500">Vehículo: <strong class="text-slate-800 dark:text-white">{{ $transfer->vehicle?->label() ?? 'Sin asignar' }}</strong></p>
            <p class="text-xs text-slate-500">Motorista: <strong class="text-slate-800 dark:text-white">{{ $transfer->driver?->fullName() ?? 'Sin asignar' }}</strong></p>
            @if($transfer->vehicleLocation)
                <p class="text-xs text-slate-500">Espacio de carga: <strong class="text-slate-800 dark:text-white font-mono">{{ $transfer->vehicleLocation->code }}</strong></p>
            @endif
            @if($enVehiculo > 0 && ! $antesDeDespachar)
                <p class="text-xs font-bold text-orange-600 dark:text-orange-400">En el vehículo: {{ \App\Http\Controllers\InventoryTransferController::unidades($enVehiculo) }}</p>
            @endif
        </div>
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-1.5 text-sm">
            <h3 class="text-xs font-extrabold uppercase tracking-wider text-slate-400">Traslado</h3>
            <p class="text-xs text-slate-500">Fecha: <strong class="text-slate-800 dark:text-white">{{ $transfer->transfer_date?->format('d/m/Y') }}</strong></p>
            <p class="text-xs text-slate-500">Autorizado: <strong class="text-slate-800 dark:text-white">{{ $transfer->authorized_at ? $transfer->authorized_at->format('d/m/Y h:i A').' · '.($transfer->authorizedBy?->username ?? '') : 'Pendiente' }}</strong></p>
            <p class="text-xs text-slate-500">{{ $antesDeDespachar ? 'Valor solicitado' : 'Valor despachado' }}: <strong class="text-[#005e66] dark:text-teal-400">${{ number_format($valor, 2) }}</strong></p>
            @if($transfer->notes)
                <p class="text-xs text-slate-500 pt-1">{{ $transfer->notes }}</p>
            @endif
        </div>
    </div>

    @if($estado === T::STATUS_PREPARING)
        <!-- Confirmar despacho: lo preparado sale de la bodega origen y entra al vehículo -->
        <form method="POST" action="{{ route('inventory-transfers.dispatch', $transfer) }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            @csrf
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800 flex flex-col md:flex-row md:items-end md:justify-between gap-4">
                <div>
                    <h2 class="font-extrabold text-slate-800 dark:text-white">Preparar y confirmar despacho</h2>
                    <p class="text-xs text-slate-400">Indique lo preparado de cada lote (puede ser menos de lo solicitado). Al confirmar, sale de su ubicación y entra al vehículo.</p>
                    @error('prepared')
                        <p class="text-rose-500 text-xs font-semibold mt-2">{{ $message }}</p>
                    @enderror
                </div>
                <div class="md:w-72">
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Espacio del vehículo *</label>
                    <select name="id_vehicle_location" required class="{{ $inputClass }} @error('id_vehicle_location') border-rose-400 @enderror">
                        @forelse($espacios as $espacio)
                            <option value="{{ $espacio->id_vehicle_location }}" @selected((string) old('id_vehicle_location') === (string) $espacio->id_vehicle_location)>{{ $espacio->code }}{{ $espacio->capacity > 0 ? ' · capacidad '.$espacio->capacity : '' }}</option>
                        @empty
                            <option value="">El vehículo no tiene espacios activos</option>
                        @endforelse
                    </select>
                    @error('id_vehicle_location')
                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Lote</th>
                            <th class="py-3 px-4">Ubicación origen</th>
                            <th class="py-3 px-4 text-right">Solicitado</th>
                            <th class="py-3 px-4 w-40">Preparado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($transfer->details as $detalle)
                            @php $id = $detalle->id_inventory_transfer_detail; @endphp
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $detalle->product?->sku }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-bold text-[#005e66] dark:text-teal-400">{{ $detalle->lot?->lot_code }}</td>
                                <td class="py-3 px-4 font-mono text-xs">{{ $detalle->sourceLocation?->code }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_requested) }} <span class="text-xs font-sans text-slate-400">{{ $unidad($detalle) }}</span></td>
                                <td class="py-3 px-4">
                                    <input type="number" step="0.0001" min="0" max="{{ (float) $detalle->quantity_requested }}" name="prepared[{{ $id }}]"
                                        value="{{ old("prepared.$id", (float) $detalle->quantity_requested) }}" class="{{ $inputClass }} text-right font-mono @error("prepared.$id") border-rose-400 @enderror">
                                    @error("prepared.$id")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('inventory.trasladar')
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" onclick="return confirm('¿Confirmar el despacho? Lo preparado sale de {{ addslashes($transfer->sourceWarehouse?->name) }} y queda en el vehículo.')" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Confirmar despacho</button>
                </div>
            @endcan
        </form>
    @elseif($transfer->isReceivable() && $enVehiculo > 0)
        <!-- Recepción: lo recibido sale del vehículo y entra a la bodega destino -->
        <form method="POST" action="{{ route('inventory-transfers.receive', $transfer) }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            @csrf
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Verificar y confirmar recepción</h2>
                <p class="text-xs text-slate-400">Indique lo que llegó de cada lote. Lo que falte sigue en el vehículo (recepción parcial); si llegó de más, el excedente queda como sobrante.</p>
                @error('received')
                    <p class="text-rose-500 text-xs font-semibold mt-2">{{ $message }}</p>
                @enderror
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Lote</th>
                            <th class="py-3 px-4 text-right">Despachado</th>
                            <th class="py-3 px-4 text-right">Ya recibido</th>
                            <th class="py-3 px-4 text-right">En vehículo</th>
                            <th class="py-3 px-4 w-36">Recibir ahora</th>
                            <th class="py-3 px-4 w-48">Ubicación destino</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($transfer->details as $detalle)
                            @php
                                $id = $detalle->id_inventory_transfer_detail;
                                $pendiente = max(0, $detalle->pendingQuantity());
                            @endphp
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $detalle->product?->sku }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-bold text-[#005e66] dark:text-teal-400">{{ $detalle->lot?->lot_code }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_dispatched) }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_received) }}</td>
                                <td class="py-3 px-4 text-right font-mono font-bold {{ $pendiente > 0 ? 'text-orange-600 dark:text-orange-400' : 'text-slate-400' }}">{{ $qty($pendiente) }} <span class="text-xs font-sans text-slate-400">{{ $unidad($detalle) }}</span></td>
                                <td class="py-3 px-4">
                                    <input type="number" step="0.0001" min="0" name="received[{{ $id }}][quantity_received]"
                                        value="{{ old("received.$id.quantity_received", $pendiente) }}" class="{{ $inputClass }} text-right font-mono @error("received.$id.quantity_received") border-rose-400 @enderror">
                                    @error("received.$id.quantity_received")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                                <td class="py-3 px-4">
                                    <select name="received[{{ $id }}][id_destination_inventory_location]" class="{{ $inputClass }} @error("received.$id.id_destination_inventory_location") border-rose-400 @enderror">
                                        <option value="">-- Elija la ubicación --</option>
                                        @foreach($ubicacionesDestino as $ubicacion)
                                            <option value="{{ $ubicacion->id_inventory_location }}" @selected((string) old("received.$id.id_destination_inventory_location", $detalle->id_destination_inventory_location) === (string) $ubicacion->id_inventory_location)>{{ $ubicacion->location?->code }}</option>
                                        @endforeach
                                    </select>
                                    @error("received.$id.id_destination_inventory_location")
                                        <p class="text-rose-500 text-[11px] font-semibold mt-1">{{ $message }}</p>
                                    @enderror
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
            @can('inventory.recibir_traslados')
                <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                    <button type="submit" onclick="return confirm('¿Confirmar la recepción? Lo recibido entrará a {{ addslashes($transfer->destinationWarehouse?->name) }}.')" class="bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-6 rounded-xl text-sm transition-all shadow-xs">Confirmar recepción</button>
                </div>
            @endcan
        </form>
    @else
        <!-- Líneas del traslado -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Lotes del traslado</h2>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Lote</th>
                            <th class="py-3 px-4">Ubicación origen</th>
                            <th class="py-3 px-4 text-right">Solicitado</th>
                            <th class="py-3 px-4 text-right">Despachado</th>
                            <th class="py-3 px-4 text-right">Recibido</th>
                            <th class="py-3 px-4">Ubicación destino</th>
                            <th class="py-3 px-4 text-right">Costo unit.</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($transfer->details as $detalle)
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $detalle->product?->sku }}</span>
                                </td>
                                <td class="py-3 px-4 font-mono text-xs font-bold text-[#005e66] dark:text-teal-400">{{ $detalle->lot?->lot_code }}</td>
                                <td class="py-3 px-4 font-mono text-xs">{{ $detalle->sourceLocation?->code ?? 'Sin ubicación' }}</td>
                                <td class="py-3 px-4 text-right font-mono">{{ $qty($detalle->quantity_requested) }} <span class="text-xs font-sans text-slate-400">{{ $unidad($detalle) }}</span></td>
                                <td class="py-3 px-4 text-right font-mono">
                                    @if($antesDeDespachar)
                                        <span class="text-slate-400">—</span>
                                    @else
                                        {{ $qty($detalle->quantity_dispatched) }}
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold">
                                    @if($antesDeDespachar)
                                        <span class="text-slate-400">—</span>
                                    @else
                                        {{ $qty($detalle->quantity_received) }}
                                    @endif
                                </td>
                                <td class="py-3 px-4 font-mono text-xs">{{ $detalle->destinationLocation?->code ?? 'Al recibir' }}</td>
                                <td class="py-3 px-4 text-right font-mono">${{ number_format((float) $detalle->unit_cost, 4) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Discrepancias -->
    @if($discrepancias->isNotEmpty())
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-rose-100 dark:border-rose-900/60 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Discrepancias</h2>
                <p class="text-xs text-slate-400">Faltantes y daños salen del vehículo como pérdida; los sobrantes entran a la bodega destino. Se aplican al regularizar.</p>
            </div>
            <div class="overflow-x-auto">
                <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">Producto / Lote</th>
                            <th class="py-3 px-4">Tipo</th>
                            <th class="py-3 px-4 text-right">Cantidad</th>
                            <th class="py-3 px-4 text-right">Valor</th>
                            <th class="py-3 px-4">Notas</th>
                            <th class="py-3 px-4">Registró</th>
                            <th class="py-3 px-4">Estado</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                        @foreach($discrepancias as [$detalle, $discrepancia])
                            <tr>
                                <td class="py-3 px-4">
                                    <span class="font-bold text-slate-800 dark:text-white block">{{ $detalle->product?->name }}</span>
                                    <span class="text-xs font-mono text-slate-400">{{ $detalle->lot?->lot_code }}</span>
                                </td>
                                <td class="py-3 px-4 text-xs font-bold">
                                    {{ $discrepancia->typeLabel() }}
                                    @if($discrepancia->destinationLocation)
                                        <span class="block font-mono font-normal text-slate-400">Entra en {{ $discrepancia->destinationLocation->code }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-right font-mono font-bold">{{ $qty($discrepancia->quantity) }}</td>
                                <td class="py-3 px-4 text-right font-mono">${{ number_format((float) $discrepancia->quantity * (float) $detalle->unit_cost, 2) }}</td>
                                <td class="py-3 px-4 text-xs">{{ $discrepancia->notes }}</td>
                                <td class="py-3 px-4 text-xs">{{ $discrepancia->user?->username }} · {{ $discrepancia->created_at?->format('d/m/Y h:i A') }}</td>
                                <td class="py-3 px-4">
                                    <span class="px-2 py-1 rounded-lg text-xs font-extrabold {{ $discrepancia->regularized_at ? 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300' : 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300' }}">{{ $discrepancia->regularized_at ? 'Regularizada' : 'Por regularizar' }}</span>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @endif

    <!-- Seguimiento -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6">
        <h2 class="font-extrabold text-slate-800 dark:text-white mb-4">Seguimiento</h2>
        <ol class="relative border-l border-slate-200 dark:border-slate-700 ml-2 space-y-4">
            @foreach($seguimiento as $paso)
                <li class="ml-5">
                    <span class="absolute -left-1.5 mt-1.5 w-3 h-3 rounded-full bg-[#005e66] dark:bg-teal-400"></span>
                    <p class="text-sm font-bold text-slate-800 dark:text-white">{{ $paso->titulo }}</p>
                    <p class="text-xs text-slate-500">{{ $paso->fecha->format('d/m/Y h:i A') }}{{ $paso->detalle ? ' · '.$paso->detalle : '' }}</p>
                </li>
            @endforeach
        </ol>
    </div>
</div>
@endsection
