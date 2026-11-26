@extends('layouts.app')
@section('title', 'Venta '.$sale->sale_code)
@section('content')
@php $verCosto = auth()->user()?->can('sales.rentabilidad'); @endphp
<div class="animate-fade-in duration-300 max-w-4xl mx-auto">
    <header class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-primary dark:text-accent tracking-tight">Venta {{ $sale->sale_code }}</h1>
            <p class="text-slate-400 text-sm font-semibold mt-1">{{ $sale->sale_date?->format('d/m/Y H:i') }} · {{ $sale->statusLabel() }}</p>
        </div>
        <div class="flex items-center gap-2">
            <a href="{{ route('sales.ticket', $sale) }}" target="_blank" class="px-4 py-2 rounded-xl bg-slate-700 hover:bg-slate-800 text-white font-bold text-sm">Ticket</a>
            <a href="{{ route('sales.pdf', $sale) }}" target="_blank" class="px-4 py-2 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Ver PDF</a>
            @can('sales.crear')
                <form action="{{ route('sales.send-receipt', $sale) }}" method="POST">
                    @csrf
                    <button type="submit" class="px-4 py-2 rounded-xl bg-info-soft text-info hover:brightness-95 font-bold text-sm">
                        {{ $sale->emailed_at ? 'Reenviar comprobante' : 'Enviar comprobante' }}
                    </button>
                </form>
            @endcan
            <a href="{{ route('sales.index') }}" class="px-4 py-2 rounded-xl bg-sunken text-muted font-bold text-sm">Volver</a>
        </div>
    </header>

    @if($sale->emailed_at)
        <div class="mb-4 text-xs font-semibold text-emerald-600">Comprobante enviado a {{ $sale->customer->email ?? 'el cliente' }} el {{ $sale->emailed_at->format('d/m/Y H:i') }}.</div>
    @elseif(!$sale->customer?->email)
        <div class="mb-4 text-xs font-semibold text-amber-600">El cliente no tiene correo: no se envió el comprobante.</div>
    @endif

    @if(session('success'))
        <div class="mb-4 p-4 rounded-2xl bg-emerald-50 border border-emerald-200 text-emerald-700 text-sm font-semibold">{{ session('success') }}</div>
    @endif

    <div class="ui-surface p-5 mb-5 grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
        <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Cliente</span><span class="font-semibold text-slate-700">{{ $sale->customer->name ?? '-' }}</span></div>
        <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Sucursal</span><span class="font-semibold text-slate-700">{{ $sale->branch->name ?? '-' }}</span></div>
        <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Documento</span><span class="font-semibold text-slate-700">{{ $sale->documentLabel() }}</span></div>
        <div><span class="block text-[10px] font-bold text-slate-400 uppercase">Forma de pago</span><span class="font-semibold text-slate-700">{{ $sale->paymentLabel() }}</span></div>
    </div>

    @if(session('info'))
        <div class="mb-4 p-4 rounded-2xl bg-sky-50 border border-sky-200 text-sky-700 text-sm font-semibold">{{ session('info') }}</div>
    @endif

    @if($sale->payment_reference || $sale->payment_bank || $sale->payment_document_number)
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-4 mb-5 text-sm text-slate-600">
            <span class="text-[10px] font-bold text-slate-400 uppercase">Datos del pago</span>
            <div class="mt-1 flex flex-wrap gap-x-4">
                @if($sale->payment_reference)<span>Referencia: <strong class="text-slate-700">{{ $sale->payment_reference }}</strong></span>@endif
                @if($sale->payment_bank)<span>Banco: <strong class="text-slate-700">{{ $sale->payment_bank }}</strong></span>@endif
                @if($sale->payment_document_number)<span>Comprobante: <strong class="text-slate-700">{{ $sale->payment_document_number }}</strong></span>@endif
            </div>
        </div>
    @endif

    <div class="ui-surface overflow-hidden mb-5">
        <table class="w-full text-left text-sm">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider bg-slate-50">
                    <th class="py-2 px-4">Producto</th>
                    <th class="py-2 px-3 text-center">Cant.</th>
                    <th class="py-2 px-3 text-right">Precio</th>
                    <th class="py-2 px-3 text-right">Desc.</th>
                    <th class="py-2 px-3 text-right">IVA</th>
                    <th class="py-2 px-3 text-right">Total</th>
                    @if($verCosto)<th class="py-2 px-3 text-right">Costo</th>@endif
                </tr>
            </thead>
            <tbody>
                @foreach($sale->details as $detail)
                    <tr class="border-t border-line">
                        <td class="py-2 px-4 font-semibold text-slate-700">
                            {{ $detail->product->name ?? '-' }}@if($detail->presentation) <span class="text-xs text-slate-400">({{ $detail->presentation->name }})</span>@endif
                            @if($verCosto && $detail->costDetails->count())
                                <div class="text-[11px] text-slate-400 font-normal mt-0.5">
                                    @foreach($detail->costDetails as $cd)
                                        Lote {{ $cd->lot->lot_code ?? $cd->id_inventory_lot }}: {{ rtrim(rtrim(number_format($cd->quantity,4),'0'),'.') }} × $ {{ number_format($cd->unit_cost,4) }}@if(!$loop->last) · @endif
                                    @endforeach
                                </div>
                            @endif
                        </td>
                        <td class="py-2 px-3 text-center text-slate-600">{{ rtrim(rtrim(number_format($detail->quantity,4),'0'),'.') }} {{ $detail->unit->name ?? '' }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">$ {{ number_format($detail->unit_price, 2) }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">$ {{ number_format($detail->discount, 2) }}</td>
                        <td class="py-2 px-3 text-right text-slate-600">$ {{ number_format($detail->tax_amount, 2) }}</td>
                        <td class="py-2 px-3 text-right font-bold text-slate-800">$ {{ number_format($detail->total, 2) }}</td>
                        @if($verCosto)<td class="py-2 px-3 text-right text-slate-500">$ {{ number_format($detail->total_cost, 2) }}</td>@endif
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="flex justify-end">
        <div class="ui-surface p-5 w-full md:w-80 space-y-2 text-sm">
            <div class="flex justify-between"><span class="text-slate-500 font-semibold">Subtotal</span><span class="font-bold">$ {{ number_format($sale->subtotal, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500 font-semibold">Descuento</span><span class="font-bold">$ {{ number_format($sale->discount, 2) }}</span></div>
            <div class="flex justify-between"><span class="text-slate-500 font-semibold">IVA</span><span class="font-bold">$ {{ number_format($sale->tax, 2) }}</span></div>
            <div class="flex justify-between text-base pt-2 border-t"><span class="text-slate-700 font-extrabold">Total</span><span class="font-extrabold text-primary dark:text-accent">$ {{ number_format($sale->total, 2) }}</span></div>
            @if($verCosto)
                <div class="flex justify-between pt-2 border-t text-xs"><span class="text-slate-400 font-semibold">Costo de venta (PEPS)</span><span class="font-bold text-slate-500">$ {{ number_format($sale->total_cost, 2) }}</span></div>
                <div class="flex justify-between text-xs"><span class="text-slate-400 font-semibold">Utilidad</span><span class="font-bold text-emerald-600">$ {{ number_format($sale->subtotal - $sale->total_cost, 2) }}</span></div>
            @endif
        </div>
    </div>
</div>

@if(request('ticket'))
    <script>window.open('{{ route('sales.ticket', $sale) }}', '_blank');</script>
@endif
@endsection
