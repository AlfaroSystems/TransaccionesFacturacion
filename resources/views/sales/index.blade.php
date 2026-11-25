@extends('layouts.app')
@section('title', 'Ventas')
@section('content')
<div class="animate-fade-in duration-300">
    <header class="mb-8 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Ventas</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Ventas registradas y entregadas.</p>
        </div>
        <div class="flex items-center gap-2">
            @can('sales.rentabilidad')
                <a href="{{ route('sales.report') }}" class="px-5 py-3 rounded-full bg-sky-50 text-sky-600 hover:bg-sky-100 font-bold text-sm">Rentabilidad</a>
            @endcan
            @can('sales.crear')
                <a href="{{ route('sales.create') }}"
                    class="flex items-center justify-center gap-2 px-5 py-3 bg-customTeal-800 hover:bg-customTeal-500 text-white rounded-full font-bold text-sm shadow-md transition-all">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4" /></svg>
                    <span>Nueva Venta</span>
                </a>
            @endcan
        </div>
    </header>

    <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 mb-6">
        <form method="GET" action="{{ route('sales.index') }}" class="flex flex-col sm:flex-row gap-4">
            <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por código, documento o cliente..."
                class="flex-1 px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm">Buscar</button>
            @if(request('search'))<a href="{{ route('sales.index') }}" class="px-5 py-2.5 bg-slate-100 text-slate-600 font-bold rounded-xl text-sm">Limpiar</a>@endif
        </form>
    </div>

    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6 pl-10">Código / Fecha</th>
                    <th class="py-3 px-6">Cliente</th>
                    <th class="py-3 px-6">Documento</th>
                    <th class="py-3 px-6">Pago</th>
                    <th class="py-3 px-6 text-right">Total</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    <tr class="group hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm">
                            <span class="font-bold text-[#005e66] text-xs">{{ $sale->sale_code }}</span>
                            <span class="block text-slate-500 text-xs">{{ $sale->sale_date?->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm font-semibold text-slate-700">{{ $sale->customer->name ?? '-' }}</td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">{{ $sale->documentLabel() }}</td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-slate-600">{{ $sale->paymentLabel() }}</td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm text-right font-extrabold text-slate-800">$ {{ number_format($sale->total, 2) }}</td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            @php
                                $badge = match($sale->status) {
                                    'delivered' => 'bg-emerald-100 text-emerald-700 border-emerald-200',
                                    'cancelled' => 'bg-rose-100 text-rose-700 border-rose-200',
                                    default => 'bg-slate-100 text-slate-600 border-slate-200',
                                };
                            @endphp
                            <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full {{ $badge }} border uppercase tracking-wider">{{ $sale->statusLabel() }}</span>
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <a href="{{ route('sales.show', $sale) }}" class="inline-flex p-2.5 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100" title="Ver">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" /><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" /></svg>
                            </a>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-slate-400 font-semibold">No hay ventas registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>

    @if($sales->hasPages())<div class="mt-6">{{ $sales->links() }}</div>@endif
</div>
@endsection
