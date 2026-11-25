@extends('layouts.app')
@section('title', 'Rentabilidad de Ventas')
@section('content')
<div class="animate-fade-in duration-300">
    <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Rentabilidad de Ventas</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Ingreso (sin IVA), costo de venta (PEPS) y utilidad de las ventas entregadas.</p>
        </div>
        <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Volver</a>
    </header>

    <form method="GET" action="{{ route('sales.report') }}" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 mb-6 flex flex-col sm:flex-row items-end gap-4">
        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Desde</label>
            <input type="date" name="from" value="{{ $from->toDateString() }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <div>
            <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Hasta</label>
            <input type="date" name="to" value="{{ $to->toDateString() }}" class="px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
        </div>
        <button type="submit" class="px-5 py-2.5 bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold rounded-xl text-sm">Filtrar</button>
    </form>

    <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 mb-6">
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4">
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Ventas</span>
            <span class="text-2xl font-extrabold text-slate-800">{{ $sales->count() }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4">
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Ingreso (sin IVA)</span>
            <span class="text-2xl font-extrabold text-slate-800">$ {{ number_format($ingresos, 2) }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4">
            <span class="block text-[10px] font-bold text-slate-400 uppercase">Costo (PEPS)</span>
            <span class="text-2xl font-extrabold text-slate-800">$ {{ number_format($costo, 2) }}</span>
        </div>
        <div class="bg-white rounded-2xl border border-emerald-100 shadow-xs p-4">
            <span class="block text-[10px] font-bold text-emerald-500 uppercase">Utilidad</span>
            <span class="text-2xl font-extrabold text-emerald-600">$ {{ number_format($utilidad, 2) }}</span>
            <span class="block text-[11px] font-semibold text-slate-400">Margen {{ $ingresos > 0 ? number_format($utilidad / $ingresos * 100, 1) : '0.0' }}%</span>
        </div>
    </div>

    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-2">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-2 px-5 pl-8">Venta</th>
                    <th class="py-2 px-5">Cliente</th>
                    <th class="py-2 px-5 text-right">Ingreso</th>
                    <th class="py-2 px-5 text-right">Costo</th>
                    <th class="py-2 px-5 text-right">Utilidad</th>
                </tr>
            </thead>
            <tbody>
                @forelse($sales as $sale)
                    @php $u = (float) $sale->subtotal - (float) $sale->total_cost; @endphp
                    <tr>
                        <td class="py-3 px-5 bg-white rounded-l-2xl border-l border-y border-slate-100 text-sm">
                            <a href="{{ route('sales.show', $sale) }}" class="font-bold text-[#005e66]">{{ $sale->sale_code }}</a>
                            <span class="block text-xs text-slate-400">{{ $sale->sale_date?->format('d/m/Y') }}</span>
                        </td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-slate-600">{{ $sale->customer->name ?? '-' }}</td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-right text-slate-700">$ {{ number_format($sale->subtotal, 2) }}</td>
                        <td class="py-3 px-5 bg-white border-y border-slate-100 text-sm text-right text-slate-500">$ {{ number_format($sale->total_cost, 2) }}</td>
                        <td class="py-3 px-5 bg-white rounded-r-2xl border-r border-y border-slate-100 text-sm text-right font-bold {{ $u >= 0 ? 'text-emerald-600' : 'text-rose-600' }}">$ {{ number_format($u, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-12 text-center text-slate-400 font-semibold">No hay ventas entregadas en el período.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
</div>
@endsection
