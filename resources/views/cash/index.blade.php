@extends('layouts.app')
@section('title', 'Caja')
@section('content')
<div class="animate-fade-in duration-300">
    <header class="mb-6">
        <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight">Caja</h1>
        <p class="text-slate-400 text-sm font-semibold mt-1">Turno de caja y arqueo. El punto de venta exige una caja abierta.</p>
    </header>

    @if ($errors->any())
        <div class="mb-4 p-4 rounded-2xl bg-rose-50 border border-rose-200 text-rose-700 text-sm font-semibold">
            <ul class="list-disc list-inside">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
        </div>
    @endif

    @if($sinSucursal)
        <div class="p-6 rounded-2xl bg-amber-50 border border-amber-200 text-amber-700 font-semibold">Tu usuario no tiene una sucursal asignada: no puede abrir caja.</div>
    @elseif($open)
        {{-- TURNO ABIERTO --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-6 mb-6">
            <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-5">
                <div>
                    <span class="px-2 py-0.5 text-[10px] font-extrabold rounded-full bg-emerald-100 text-emerald-700 border border-emerald-200 uppercase">Caja abierta</span>
                    <h2 class="text-lg font-extrabold text-slate-800 mt-1">{{ $open->shift_code }}</h2>
                    <p class="text-xs text-slate-400 font-semibold">Abierta {{ $open->opened_at?->format('d/m/Y H:i') }} por {{ $open->user->name ?? '—' }}</p>
                </div>
                <div class="flex items-center gap-2">
                    @can('cash.egreso')
                        <button type="button" onclick="openModal('outflow-modal')" class="px-4 py-2 rounded-xl bg-amber-50 text-amber-600 hover:bg-amber-100 font-bold text-sm">Registrar egreso</button>
                    @endcan
                    @can('cash.cerrar')
                        <button type="button" onclick="openModal('close-modal')" class="px-4 py-2 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm">Cerrar caja</button>
                    @endcan
                </div>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-4 text-sm">
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Apertura</span><span class="font-extrabold text-slate-800">$ {{ number_format($open->opening_amount, 2) }}</span></div>
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Ventas</span><span class="font-extrabold text-slate-800">$ {{ number_format($open->total_sales, 2) }}</span></div>
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Egresos</span><span class="font-extrabold text-slate-800">$ {{ number_format($open->total_outflows, 2) }}</span></div>
                <div class="p-3 rounded-xl bg-emerald-50"><span class="block text-[10px] font-bold text-emerald-500 uppercase">Efectivo esperado</span><span class="font-extrabold text-emerald-700">$ {{ number_format($open->expectedCash(), 2) }}</span></div>
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Efectivo</span><span class="font-bold text-slate-700">$ {{ number_format($open->total_cash, 2) }}</span></div>
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Tarjeta</span><span class="font-bold text-slate-700">$ {{ number_format($open->total_card, 2) }}</span></div>
                <div class="p-3 rounded-xl bg-slate-50"><span class="block text-[10px] font-bold text-slate-400 uppercase">Cheque</span><span class="font-bold text-slate-700">$ {{ number_format($open->total_check, 2) }}</span></div>
            </div>

            @if($open->outflows->count())
                <div class="mt-5">
                    <h3 class="text-xs font-bold text-slate-500 uppercase mb-2">Egresos</h3>
                    <table class="w-full text-left text-sm">
                        <tbody>
                            @foreach($open->outflows as $egreso)
                                <tr class="border-b border-slate-50">
                                    <td class="py-1.5 text-slate-600">{{ $egreso->concept }}</td>
                                    <td class="py-1.5 text-slate-400 text-xs">{{ $egreso->user->name ?? '' }} · {{ $egreso->created_at?->format('d/m/Y H:i') }}</td>
                                    <td class="py-1.5 text-right font-bold text-rose-600">$ {{ number_format($egreso->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    @else
        {{-- SIN TURNO --}}
        <div class="bg-white rounded-2xl border border-slate-100 shadow-xs p-8 mb-6 text-center">
            <p class="text-slate-500 font-semibold mb-4">No hay una caja abierta en tu sucursal.</p>
            @can('cash.abrir')
                <button type="button" onclick="openModal('open-modal')" class="px-6 py-3 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Abrir caja</button>
            @endcan
        </div>
    @endif

    {{-- HISTORIAL --}}
    <h3 class="text-sm font-extrabold text-slate-600 mb-3">Turnos cerrados</h3>
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-y-2">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-2 px-4 pl-6">Turno</th>
                    <th class="py-2 px-4 text-right">Ventas</th>
                    <th class="py-2 px-4 text-right">Esperado</th>
                    <th class="py-2 px-4 text-right">Contado</th>
                    <th class="py-2 px-4 text-right">Diferencia</th>
                </tr>
            </thead>
            <tbody>
                @forelse($history as $shift)
                    <tr>
                        <td class="py-2 px-4 pl-6 bg-white rounded-l-xl border-l border-y border-slate-100 text-sm">
                            <span class="font-bold text-slate-700">{{ $shift->shift_code }}</span>
                            <span class="block text-xs text-slate-400">{{ $shift->closed_at?->format('d/m/Y H:i') }}</span>
                        </td>
                        <td class="py-2 px-4 bg-white border-y border-slate-100 text-sm text-right">$ {{ number_format($shift->total_sales, 2) }}</td>
                        <td class="py-2 px-4 bg-white border-y border-slate-100 text-sm text-right text-slate-500">$ {{ number_format($shift->expectedCash(), 2) }}</td>
                        <td class="py-2 px-4 bg-white border-y border-slate-100 text-sm text-right">$ {{ number_format($shift->counted_amount, 2) }}</td>
                        <td class="py-2 px-4 bg-white rounded-r-xl border-r border-y border-slate-100 text-sm text-right font-bold {{ (float) $shift->difference == 0.0 ? 'text-slate-500' : 'text-rose-600' }}">$ {{ number_format($shift->difference, 2) }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="py-8 text-center text-slate-400 font-semibold">No hay turnos cerrados.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    @if($history->hasPages())<div class="mt-4">{{ $history->links() }}</div>@endif
</div>

{{-- MODALES --}}
@can('cash.abrir')
    <div id="open-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
        <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl mx-4">
            <div class="flex items-center justify-between border-b pb-4 mb-5"><h3 class="text-lg font-extrabold text-slate-800">Abrir caja</h3><button type="button" onclick="closeModal('open-modal')" class="text-slate-400 text-xl">✕</button></div>
            <form action="{{ route('cash.open') }}" method="POST" class="space-y-4">
                @csrf
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Monto de apertura *</label>
                    <input type="number" step="0.01" min="0" name="opening_amount" value="0" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                </div>
                <div class="flex justify-end gap-3 pt-3 border-t">
                    <button type="button" onclick="closeModal('open-modal')" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-customTeal-500 text-white font-bold text-sm">Abrir</button>
                </div>
            </form>
        </div>
    </div>
@endcan

@if($open)
    @can('cash.egreso')
        <div id="outflow-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl mx-4">
                <div class="flex items-center justify-between border-b pb-4 mb-5"><h3 class="text-lg font-extrabold text-slate-800">Registrar egreso</h3><button type="button" onclick="closeModal('outflow-modal')" class="text-slate-400 text-xl">✕</button></div>
                <form action="{{ route('cash.outflow', $open) }}" method="POST" class="space-y-4">
                    @csrf
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Monto *</label>
                        <input type="number" step="0.01" min="0.01" name="amount" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Concepto *</label>
                        <input type="text" name="concept" maxlength="200" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button type="button" onclick="closeModal('outflow-modal')" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm">Registrar</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan

    @can('cash.cerrar')
        <div id="close-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-xs">
            <div class="bg-white rounded-3xl p-6 max-w-sm w-full shadow-2xl mx-4">
                <div class="flex items-center justify-between border-b pb-4 mb-5"><h3 class="text-lg font-extrabold text-slate-800">Cerrar caja (arqueo)</h3><button type="button" onclick="closeModal('close-modal')" class="text-slate-400 text-xl">✕</button></div>
                <form action="{{ route('cash.close', $open) }}" method="POST" class="space-y-4">
                    @csrf
                    <p class="text-sm text-slate-500">Efectivo esperado: <strong class="text-slate-800">$ {{ number_format($open->expectedCash(), 2) }}</strong></p>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Efectivo contado *</label>
                        <input type="number" step="0.01" min="0" name="counted_amount" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Observaciones</label>
                        <input type="text" name="notes" maxlength="500" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm">
                    </div>
                    <div class="flex justify-end gap-3 pt-3 border-t">
                        <button type="button" onclick="closeModal('close-modal')" class="px-5 py-2.5 rounded-xl bg-slate-100 text-slate-600 font-bold text-sm">Cancelar</button>
                        <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm">Cerrar caja</button>
                    </div>
                </form>
            </div>
        </div>
    @endcan
@endif
@endsection
