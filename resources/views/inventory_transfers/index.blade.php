@extends('layouts.app')
@section('title', 'Traslados de Inventario')

@section('content')
@php
    $statusClasses = [
        'draft' => 'bg-slate-100 text-slate-600 dark:bg-slate-800 dark:text-slate-300',
        'dispatched' => 'bg-amber-50 text-amber-700 dark:bg-amber-950/60 dark:text-amber-300',
        'received' => 'bg-emerald-50 text-emerald-700 dark:bg-emerald-950/60 dark:text-emerald-300',
        'cancelled' => 'bg-rose-50 text-rose-700 dark:bg-rose-950/60 dark:text-rose-300',
    ];
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Traslados</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Traslados de Inventario</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Casa Matriz reparte a las sucursales lo que entró con las compras: se despacha desde la bodega origen y se recibe en la destino.</p>
        </div>
        @can('inventory.trasladar')
            <a href="{{ route('inventory-transfers.create') }}" class="inline-flex items-center justify-center gap-2 bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                Nuevo Traslado
            </a>
        @endcan
    </div>

    <!-- Filtros -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('inventory-transfers.index') }}" class="flex flex-col md:flex-row gap-3 md:items-center">
            <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código (TRF-...)" class="flex-1 px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm outline-hidden focus:ring-2 focus:ring-[#005e66]">
            <select name="status" class="md:w-56 px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm">
                <option value="">Todos los estados</option>
                @foreach(\App\Models\InventoryTransfer::STATUS_LABELS as $value => $label)
                    <option value="{{ $value }}" @selected($value === $status)>{{ $label }}</option>
                @endforeach
            </select>
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Filtrar</button>
            @if($search || $status)
                <a href="{{ route('inventory-transfers.index') }}" class="text-center text-xs font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">Limpiar</a>
            @endif
        </form>
    </div>

    <!-- Listado -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-6">Código</th>
                        <th class="py-4 px-6">Fecha</th>
                        <th class="py-4 px-6">Origen → Destino</th>
                        <th class="py-4 px-6 text-center">Lotes</th>
                        <th class="py-4 px-6">Estado</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($transfers as $transfer)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono font-bold text-[#005e66] dark:text-teal-400">{{ $transfer->transfer_code }}</td>
                            <td class="py-4 px-6 text-xs">{{ $transfer->transfer_date?->format('d/m/Y') }}</td>
                            <td class="py-4 px-6">
                                <span class="font-semibold text-slate-800 dark:text-white">{{ $transfer->sourceWarehouse?->name }}</span>
                                <span class="text-slate-400 mx-1">→</span>
                                <span class="font-semibold text-slate-800 dark:text-white">{{ $transfer->destinationWarehouse?->name }}</span>
                            </td>
                            <td class="py-4 px-6 text-center font-mono">{{ $transfer->details_count }}</td>
                            <td class="py-4 px-6">
                                <span class="px-2 py-1 rounded-lg text-xs font-extrabold whitespace-nowrap {{ $statusClasses[$transfer->status] ?? '' }}">{{ $transfer->statusLabel() }}</span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <a href="{{ route('inventory-transfers.show', $transfer) }}" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">Ver detalle</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-16 text-center text-slate-400 text-sm">
                                {{ $search || $status ? 'No se encontraron traslados con esos filtros.' : 'Aún no hay traslados. Cree uno para repartir a las sucursales lo que entró a Casa Matriz.' }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($transfers->hasPages())
            <div class="px-6 py-4 border-t border-slate-100 dark:border-slate-800">{{ $transfers->links() }}</div>
        @endif
    </div>
</div>
@endsection
