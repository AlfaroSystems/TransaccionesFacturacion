@extends('layouts.app')
@section('title', 'Etiquetas del lote '.$lot->lot_code)

@section('content')
@php
    $inputClass = 'w-full px-3 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm';
    $reimpresion = $lot->labelPrints->isNotEmpty();
@endphp
<div class="w-full space-y-6 animate-fade-in duration-300">
    <div class="flex flex-col md:flex-row md:items-end md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Inventario</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Etiquetas</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Etiquetas del lote <span class="font-mono">{{ $lot->lot_code }}</span></h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">{{ $lot->product?->name }} · recibido el {{ $lot->received_at?->format('d/m/Y') }} · {{ rtrim(rtrim(number_format((float) $lot->quantity_received, 4, '.', ''), '0'), '.') }} {{ $lot->product?->purchaseUnit?->name }}</p>
        </div>
        <a href="{{ route('inventory.lots.show', $lot) }}" class="text-sm font-bold text-slate-500 hover:text-slate-700 dark:hover:text-slate-300">← Volver al lote</a>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <form method="POST" action="{{ route('inventory.labels.store', $lot) }}" class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-5 space-y-4">
            @csrf
            <h2 class="font-extrabold text-slate-800 dark:text-white">{{ $reimpresion ? 'Reimprimir etiquetas' : 'Imprimir etiquetas' }}</h2>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Cantidad de etiquetas *</label>
                <input type="number" name="quantity_printed" min="1" max="500" required value="{{ old('quantity_printed', max(1, (int) ceil((float) $lot->quantity_received))) }}" class="{{ $inputClass }} @error('quantity_printed') border-rose-400 @enderror">
                @error('quantity_printed')
                    <p class="text-rose-500 text-xs font-semibold mt-1">{{ $message }}</p>
                @enderror
            </div>
            @if($reimpresion)
                <div>
                    <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Motivo de la reimpresión *</label>
                    <input type="text" name="reprint_reason" maxlength="255" required value="{{ old('reprint_reason') }}" placeholder="Ej.: etiquetas dañadas" class="{{ $inputClass }} @error('reprint_reason') border-rose-400 @enderror">
                    @error('reprint_reason')
                        <p class="text-rose-500 text-xs font-semibold mt-1">{{ $message }}</p>
                    @enderror
                </div>
            @endif
            <button type="submit" class="w-full bg-[#005e66] hover:bg-[#004d54] text-white font-bold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs">Generar e imprimir</button>
        </form>

        <div class="lg:col-span-2 bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
            <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-800">
                <h2 class="font-extrabold text-slate-800 dark:text-white">Historial de impresiones</h2>
            </div>
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3 px-4">Fecha</th>
                        <th class="py-3 px-4 text-right">Etiquetas</th>
                        <th class="py-3 px-4">Usuario</th>
                        <th class="py-3 px-4">Motivo</th>
                        <th class="py-3 px-4 text-right"></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($lot->labelPrints as $print)
                        <tr>
                            <td class="py-3 px-4 text-xs">{{ $print->print_date?->format('d/m/Y h:i A') }}</td>
                            <td class="py-3 px-4 text-right font-mono font-bold">{{ $print->quantity_printed }}</td>
                            <td class="py-3 px-4 text-xs">{{ $print->user?->username ?? 'Sistema' }}</td>
                            <td class="py-3 px-4 text-xs">{{ $print->reprint_reason ?? 'Primera impresión' }}</td>
                            <td class="py-3 px-4 text-right"><a href="{{ route('inventory.labels.print', $print) }}" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline">Ver hoja</a></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-10 text-center text-slate-400 text-sm">Aún no se han impreso etiquetas de este lote.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
