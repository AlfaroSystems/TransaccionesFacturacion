@extends('layouts.app')
@section('title', 'Ventas por Lote')
@section('content')
<div class="animate-fade-in duration-300">
    <header class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Ventas por Lote</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">Trazabilidad: qué lotes se vendieron, cuánto (unidades base) y su costo.</p>
        </div>
        <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Volver</a>
    </header>

    <form method="GET" action="{{ route('sales.report-lot') }}" class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 mb-6 flex flex-col sm:flex-row items-end gap-4">
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

    <section class="overflow-x-auto">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider bg-slate-50">
                    <th class="py-2 px-4">Lote</th>
                    <th class="py-2 px-4">Vence</th>
                    <th class="py-2 px-4">Producto</th>
                    <th class="py-2 px-4">Venta</th>
                    <th class="py-2 px-4">Fecha</th>
                    <th class="py-2 px-4 text-right">Cant. (u. base)</th>
                    <th class="py-2 px-4 text-right">Costo</th>
                </tr>
            </thead>
            <tbody>
                @forelse($rows as $row)
                    <tr class="border-b border-slate-50">
                        <td class="py-2 px-4 font-bold text-[#005e66]">{{ $row->lot->lot_code ?? '—' }}</td>
                        <td class="py-2 px-4 text-slate-500">{{ $row->lot?->expiration_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="py-2 px-4 text-slate-700">{{ $row->lot?->product?->name ?? '—' }}</td>
                        <td class="py-2 px-4">
                            @if($row->saleDetail?->sale)
                                <a href="{{ route('sales.show', $row->saleDetail->sale) }}" class="text-sky-600 font-semibold">{{ $row->saleDetail->sale->sale_code }}</a>
                            @else — @endif
                        </td>
                        <td class="py-2 px-4 text-slate-500">{{ $row->saleDetail?->sale?->sale_date?->format('d/m/Y') ?? '—' }}</td>
                        <td class="py-2 px-4 text-right text-slate-700">{{ rtrim(rtrim(number_format($row->quantity, 4), '0'), '.') }}</td>
                        <td class="py-2 px-4 text-right font-bold text-slate-800">$ {{ number_format($row->total_cost, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-slate-400 font-semibold">No hay ventas por lote en el período.</td></tr>
                @endforelse
            </tbody>
            @if($rows->count())
                <tfoot>
                    <tr class="font-extrabold text-slate-800 border-t-2 border-slate-200">
                        <td class="py-2 px-4" colspan="5">Total</td>
                        <td class="py-2 px-4 text-right">{{ rtrim(rtrim(number_format($totalCantidad, 4), '0'), '.') }}</td>
                        <td class="py-2 px-4 text-right">$ {{ number_format($totalCosto, 2) }}</td>
                    </tr>
                </tfoot>
            @endif
        </table>
    </section>
</div>
@endsection
