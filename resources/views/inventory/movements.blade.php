@extends('layouts.app')
@section('title', 'Movimientos de Inventario')

@section('content')
@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4), '0'), '.');
    $typeClasses = [
        'purchase_receipt' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'purchase_cancel' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
        'cost_adjustment' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
    ];
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div>
        <div class="flex items-center gap-2">
            <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
            <span class="text-slate-400 dark:text-slate-600">•</span>
            <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Kardex</span>
        </div>
        <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Movimientos de Inventario</h1>
        <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Cada entrada, salida y ajuste de costo de los lotes, con su documento de origen.</p>
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('inventory.movements') }}" class="grid grid-cols-1 md:grid-cols-6 gap-3 items-end">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Buscar</label>
                <input type="text" name="search" value="{{ $search }}" placeholder="Producto, SKU o lote..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm outline-hidden focus:ring-2 focus:ring-[#005e66]">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Bodega</label>
                <select name="id_warehouse" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                    <option value="">Todas</option>
                    @foreach($warehouses as $warehouse)
                        <option value="{{ $warehouse->id_warehouse }}" @selected($warehouse->id_warehouse == $warehouseId)>{{ $warehouse->name }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Tipo</label>
                <select name="type" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                    <option value="">Todos</option>
                    @foreach(\App\Models\InventoryMovement::TYPE_LABELS as $value => $label)
                        <option value="{{ $value }}" @selected($value === $type)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Desde</label>
                <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Hasta</label>
                <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
            </div>
            <div class="md:col-span-6 flex justify-end gap-3">
                @if(request()->hasAny(['search', 'id_warehouse', 'type', 'date_from', 'date_to']))
                    <a href="{{ route('inventory.movements') }}" class="px-4 py-2.5 text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Limpiar</a>
                @endif
                <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Filtrar</button>
            </div>
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-5">Fecha</th>
                        <th class="py-4 px-5">Tipo</th>
                        <th class="py-4 px-5">Producto / Lote</th>
                        <th class="py-4 px-5">Bodega / Ubicación</th>
                        <th class="py-4 px-5 text-right">Entrada</th>
                        <th class="py-4 px-5 text-right">Salida</th>
                        <th class="py-4 px-5 text-right">Costo Unit.</th>
                        <th class="py-4 px-5 text-right">Valor</th>
                        <th class="py-4 px-5">Documento</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($movements as $movement)
                        @php $salida = (float) $movement->quantity_out > 0; @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-5 whitespace-nowrap">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $movement->movement_date->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-400 block">{{ $movement->movement_date->format('h:i A') }}</span>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold whitespace-nowrap {{ $typeClasses[$movement->movement_type] ?? 'bg-slate-100 text-slate-600' }}">{{ $movement->typeLabel() }}</span>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-800 dark:text-white block">{{ $movement->product?->name ?? 'Producto' }}</span>
                                <span class="text-xs font-mono text-slate-400">{{ $movement->lot?->lot_code }}</span>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="font-semibold text-slate-700 dark:text-slate-200 block">{{ $movement->warehouse?->name ?? 'N/A' }}</span>
                                <span class="text-xs font-mono text-slate-400">{{ $movement->location?->code ?? 'Sin ubicación' }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold text-emerald-600 dark:text-emerald-400">{{ (float) $movement->quantity_in > 0 ? $qty($movement->quantity_in) : '' }}</td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold text-rose-600 dark:text-rose-400">{{ $salida ? $qty($movement->quantity_out) : '' }}</td>
                            <td class="py-3.5 px-5 text-right font-mono">${{ number_format((float) $movement->unit_cost, 4) }}</td>
                            <td class="py-3.5 px-5 text-right font-mono font-semibold whitespace-nowrap {{ $salida || (float) $movement->total_cost < 0 ? 'text-rose-600 dark:text-rose-400' : 'text-slate-800 dark:text-white' }}">
                                {{ $salida ? '-' : '' }}${{ number_format((float) $movement->total_cost, 2) }}
                            </td>
                            <td class="py-3.5 px-5 text-xs whitespace-nowrap">
                                @if($movement->reference_type === \App\Models\InventoryMovement::REFERENCE_PURCHASE && isset($purchaseCodes[$movement->reference_id]))
                                    <a href="{{ route('purchases.show', $movement->reference_id) }}" class="font-mono font-bold text-[#005e66] dark:text-teal-400 hover:underline">{{ $purchaseCodes[$movement->reference_id] }}</a>
                                @endif
                                <span class="block text-slate-400">{{ $movement->user?->username }}</span>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-16 text-center text-slate-400 text-sm">
                                {{ request()->hasAny(['search', 'id_warehouse', 'type', 'date_from', 'date_to']) ? 'No se encontraron movimientos para los filtros.' : 'Aún no hay movimientos: se generan al recibir las compras.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($movements->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
