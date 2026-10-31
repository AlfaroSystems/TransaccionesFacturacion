@extends('layouts.app')
@section('title', 'Lotes de Inventario')

@section('content')
@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    $tabClass = fn (bool $active) => $active
        ? 'bg-customTeal-800 dark:bg-sky-600 text-white shadow-sm'
        : 'bg-white dark:bg-slate-800 hover:bg-slate-100 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700';
    $statusClasses = [
        'pending_placement' => 'bg-amber-50 text-amber-700 border-amber-200 dark:bg-amber-950 dark:text-amber-300 dark:border-amber-800',
        'active' => 'bg-teal-50 text-[#005e66] border-teal-200 dark:bg-teal-950 dark:text-teal-300 dark:border-teal-800',
        'cancelled' => 'bg-slate-100 text-slate-500 border-slate-200 dark:bg-slate-800 dark:text-slate-400 dark:border-slate-700',
    ];
    $tabs = \App\Models\InventoryLot::STATUS_LABELS + ['todos' => 'Todos'];
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Lotes</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Lotes</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Se crean al aplicar el retaceo de cada compra. Se viñetean, se ubican en bodega y al confirmar su colocación entran al inventario.</p>
        </div>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-4 flex flex-col md:flex-row gap-3 md:items-center">
        <div class="flex flex-wrap gap-2">
            @foreach($tabs as $clave => $nombre)
                @php $activo = $clave === 'todos' ? $status === null : $status === $clave; @endphp
                <a href="{{ route('inventory.lots.index', array_filter(['status' => $clave, 'search' => $search])) }}" class="px-4 py-2 rounded-full text-xs font-bold whitespace-nowrap transition-all {{ $tabClass($activo) }}">
                    {{ $nombre }}
                    @if($clave !== 'todos')
                        <span class="ml-1 opacity-70">{{ $conteos[$clave] ?? 0 }}</span>
                    @endif
                </a>
            @endforeach
        </div>
        <form method="GET" action="{{ route('inventory.lots.index') }}" class="flex-1 flex gap-3">
            <input type="hidden" name="status" value="{{ $status ?? 'todos' }}">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar lote, producto o SKU..." class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Buscar</button>
        </form>
    </div>

    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-5">Lote</th>
                        <th class="py-4 px-5">Producto</th>
                        <th class="py-4 px-5">Compra / Bodega</th>
                        <th class="py-4 px-5 text-right">Cantidad</th>
                        <th class="py-4 px-5 text-right">Ubicado</th>
                        <th class="py-4 px-5 text-center">Viñetas</th>
                        <th class="py-4 px-5">Estado</th>
                        <th class="py-4 px-5"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($lots as $lot)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-3.5 px-5">
                                <span class="font-mono font-extrabold text-[#005e66] dark:text-teal-400">{{ $lot->lot_code }}</span>
                                <span class="text-xs text-slate-400 block">Recibido el {{ $lot->received_at?->format('d/m/Y') }}</span>
                                @if($lot->supplier_lot_number || $lot->expiration_date)
                                    <span class="text-xs text-slate-500 dark:text-slate-400 block">{{ $lot->supplier_lot_number ? 'Prov. '.$lot->supplier_lot_number : '' }}{{ $lot->supplier_lot_number && $lot->expiration_date ? ' · ' : '' }}{{ $lot->expiration_date ? 'Vence '.$lot->expiration_date->format('d/m/Y') : '' }}</span>
                                @endif
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="font-bold text-slate-800 dark:text-white block">{{ $lot->product?->name }}</span>
                                <span class="text-xs font-mono text-slate-400">{{ $lot->product?->sku }}</span>
                            </td>
                            <td class="py-3.5 px-5">
                                <span class="font-mono text-xs font-bold block">{{ $lot->purchaseDetail?->purchase?->purchase_code ?? '—' }}</span>
                                <span class="text-xs text-slate-400">{{ $lot->warehouseAllBranches?->name }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right font-mono font-bold whitespace-nowrap">{{ $qty($lot->quantity_received) }} <span class="text-xs font-sans font-semibold text-slate-400">{{ $lot->purchaseDetail?->unit?->name }}</span></td>
                            <td class="py-3.5 px-5 text-right font-mono text-xs whitespace-nowrap">
                                @if($lot->isPending())
                                    {{ $qty($lot->placements_sum_quantity ?? 0) }} de {{ $qty($lot->quantity_received) }}
                                @elseif($lot->status === 'active')
                                    ✓
                                @endif
                            </td>
                            <td class="py-3.5 px-5 text-center text-xs">{{ $lot->label_prints_count ? '✓' : '—' }}</td>
                            <td class="py-3.5 px-5">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold border whitespace-nowrap {{ $statusClasses[$lot->status] ?? '' }}">{{ $lot->statusLabel() }}</span>
                            </td>
                            <td class="py-3.5 px-5 text-right">
                                <a href="{{ route('inventory.lots.show', $lot) }}" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline whitespace-nowrap">{{ $lot->isPending() ? 'Ubicar →' : 'Ver →' }}</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-16 text-center text-slate-400 text-sm">
                                {{ $status === 'pending_placement' && $search === '' ? 'No hay lotes por ubicar: se crean al aplicar el retaceo de una compra.' : 'No se encontraron lotes.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($lots->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $lots->links() }}
            </div>
        @endif
    </div>
</div>
@endsection
