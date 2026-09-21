@extends('layouts.app')
@section('title', 'Liquidación de Retaceo de Importación')

@section('content')
@php
    $statusClasses = [
        'draft'      => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600',
        'calculated' => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800',
        'applied'    => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
        'cancelled'  => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800',
    ];
    $statusNames = [
        'draft'      => 'Borrador',
        'calculated' => 'Liquidado / Calculado',
        'applied'    => 'Aplicado a Inventario',
        'cancelled'  => 'Cancelado',
    ];

    $gastosTotales = $retaceo->total_freight + $retaceo->total_expenses + $retaceo->total_dai;
    $factorIncremento = $retaceo->total_fob > 0 ? (($gastosTotales / $retaceo->total_fob) * 100) : 0;
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado con Botones de Acción -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('retaceos.index') }}" class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg hover:bg-teal-200 transition-colors">
                    Retaceos
                </a>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">Liquidación de Costos</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight font-mono">
                    {{ $retaceo->retaceo_code }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $statusClasses[$retaceo->status] ?? 'bg-slate-100 text-slate-700' }}">
                    ● {{ $statusNames[$retaceo->status] ?? ucfirst($retaceo->status) }}
                </span>
                @if($retaceo->import_policy_number)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-indigo-50 text-indigo-800 border border-indigo-200 dark:bg-indigo-950/50 dark:text-indigo-300 dark:border-indigo-800">
                        Póliza: {{ $retaceo->import_policy_number }}
                    </span>
                @endif
            </div>
            <p class="text-slate-500 text-xs mt-1">
                Liquidado el {{ $retaceo->retaceo_date ? $retaceo->retaceo_date->format('d/m/Y \a \l\a\s h:i A') : '-' }} por {{ $retaceo->user->name ?? 'Sistema' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            @can('retaceos.cambiar_estado')
                @if($retaceo->status === 'draft')
                    <button type="button" onclick="openRetaceoStatusModal('{{ route('retaceos.updateStatus', $retaceo->id_retaceo) }}', 'calculated', '¿Marcar Retaceo como Liquidado?', 'Se fijará el cálculo de costos y prorrateo para esta importación.', 'Sí, liquidar retaceo')" class="px-5 py-2.5 rounded-full bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <span>📊 Marcar Liquidado</span>
                    </button>
                @elseif($retaceo->status === 'calculated')
                    <button type="button" onclick="openRetaceoStatusModal('{{ route('retaceos.updateStatus', $retaceo->id_retaceo) }}', 'applied', '¿Aplicar Retaceo a Inventario?', 'Se considerará el costo liquidado como el costo real definitivo de entrada para los productos.', 'Sí, aplicar a inventario')" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Aplicar a Inventario</span>
                    </button>
                @endif

                @if(in_array($retaceo->status, ['draft', 'calculated']))
                    <button type="button" onclick="openRetaceoStatusModal('{{ route('retaceos.updateStatus', $retaceo->id_retaceo) }}', 'cancelled', '¿Cancelar Retaceo?', 'Esta acción no se puede deshacer y el cálculo quedará anulado.', 'Sí, cancelar retaceo')" class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Cancelar</span>
                    </button>
                @endif
            @endcan

            @if($retaceo->status === 'draft')
                @can('retaceos.editar')
                    <a href="{{ route('retaceos.edit', $retaceo->id_retaceo) }}" class="px-5 py-2.5 rounded-full bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md" title="Editar Retaceo">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Editar</span>
                    </a>
                @endcan
            @endif

            <button type="button" onclick="window.print()" class="px-5 py-2.5 rounded-full bg-[#005e66] hover:bg-[#00474f] text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Imprimir</span>
            </button>

            <a href="{{ route('retaceos.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs transition-all flex items-center justify-center gap-2 shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver</span>
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl font-semibold text-sm shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    <!-- Tarjetas de Información de Importación -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Póliza y Documentos Aduanales -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">📑</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Póliza y Documentos</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">No. Póliza / DM:</span>
                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $retaceo->import_policy_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Fecha Póliza:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->import_policy_date ? $retaceo->import_policy_date->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Factura Exterior:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->import_invoice_number ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">País Origen:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->origin_country ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Proveedor -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">🏢</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Proveedor Exterior</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 block">Proveedor:</span>
                    <span class="font-extrabold text-sm text-slate-800 dark:text-slate-100">{{ $retaceo->supplier->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Email:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->supplier->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Teléfono:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->supplier->phone ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Compra Origen -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">🧾</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Compra de Origen</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Factura Compra:</span>
                    @if($retaceo->purchase)
                        <a href="{{ route('purchases.show', $retaceo->purchase->id_purchase) }}" class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline">
                            {{ $retaceo->purchase->purchase_code }}
                        </a>
                    @else
                        <span class="text-slate-400">N/A</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Fecha Compra:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->purchase?->purchase_date ? $retaceo->purchase->purchase_date->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Sucursal Destino:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $retaceo->purchase?->branch?->name ?? 'Principal' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla Detallada de Liquidación de Costos -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <span>📦 Desglose de Prorrateo por Producto</span>
            </h3>
            <span class="text-xs font-semibold text-slate-400">{{ $retaceo->details->count() }} producto(s) costeados</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100 dark:border-slate-700">
                    <tr>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4 text-center">Cantidad</th>
                        <th class="py-3 px-4 text-right">Costo FOB</th>
                        <th class="py-3 px-4 text-right">Flete Asig.</th>
                        <th class="py-3 px-4 text-right">Gastos Asig.</th>
                        <th class="py-3 px-4 text-right">DAI / Arancel</th>
                        <th class="py-3 px-4 text-right">Costo Total</th>
                        <th class="py-3 px-4 text-right">Costo Unit. Real</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($retaceo->details as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $item->product->name ?? 'Producto #'.$item->id_product }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $item->product?->code ?? '' }}</div>
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                                {{ number_format($item->quantity, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->cost_fob, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->freight_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->expense_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->dai_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-bold text-slate-800 dark:text-slate-100">
                                ${{ number_format($item->total_cost, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-extrabold text-[#005e66] dark:text-teal-400 text-sm">
                                ${{ number_format($item->unit_cost, 4) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Desglose y Resumen Financiero -->
        <div class="flex flex-col md:flex-row justify-between items-start pt-4 border-t border-slate-100 dark:border-slate-700 gap-4">
            <div class="w-full md:w-1/2 space-y-3">
                <div class="bg-indigo-50/70 dark:bg-slate-900/50 rounded-xl p-4 border border-indigo-100 dark:border-slate-700">
                    <span class="font-extrabold text-xs uppercase tracking-wider text-indigo-900 dark:text-indigo-300 block mb-1">
                        💡 Resumen de Gastos de Internación:
                    </span>
                    <p class="text-xs text-indigo-700 dark:text-slate-300">
                        Los gastos adicionales de importación (Flete, gastos y aranceles) sumaron <strong>${{ number_format($gastosTotales, 2) }}</strong>, lo que representa un incremento medio del <strong>{{ number_format($factorIncremento, 2) }}%</strong> sobre el valor FOB de compra de la mercancía.
                    </p>
                </div>
                @if($retaceo->notes)
                    <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-xs">
                        <span class="font-bold text-slate-500 dark:text-slate-400 block mb-1">Notas:</span>
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $retaceo->notes }}</p>
                    </div>
                @endif
            </div>
            <div class="w-full md:w-80 space-y-2 text-sm bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Total FOB Mercancía:</span>
                    <span class="font-mono font-bold">${{ number_format($retaceo->total_fob, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Flete Internacional:</span>
                    <span class="font-mono font-bold">${{ number_format($retaceo->total_freight, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Gastos Aduanales / Seguro:</span>
                    <span class="font-mono font-bold">${{ number_format($retaceo->total_expenses, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Aranceles DAI:</span>
                    <span class="font-mono font-bold">${{ number_format($retaceo->total_dai, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-extrabold text-slate-800 dark:text-slate-100 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span>Costo Total Liquidado:</span>
                    <span class="font-mono text-[#005e66] dark:text-teal-400 text-xl">${{ number_format($retaceo->total_cost, 2) }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cambio de Estado -->
<div id="retaceoStatusModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 max-w-md w-full shadow-2xl text-center relative mx-4">
        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2" id="retaceoStatusModalTitle">¿Cambiar Estado?</h3>
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-6" id="retaceoStatusModalDesc"></p>
        <form id="retaceoStatusModalForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" id="retaceoStatusModalInput" value="">
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeRetaceoStatusModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-xs transition-all">Cancelar</button>
                <button type="submit" id="retaceoStatusModalBtn" class="px-5 py-2.5 bg-[#005e66] hover:bg-[#00474f] text-white font-semibold rounded-xl text-xs transition-all shadow-sm">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openRetaceoStatusModal(url, status, title, desc, btnText) {
        document.getElementById('retaceoStatusModalForm').action = url;
        document.getElementById('retaceoStatusModalInput').value = status;
        document.getElementById('retaceoStatusModalTitle').textContent = title;
        document.getElementById('retaceoStatusModalDesc').textContent = desc;
        const btn = document.getElementById('retaceoStatusModalBtn');
        btn.textContent = btnText;

        const modal = document.getElementById('retaceoStatusModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeRetaceoStatusModal() {
        const modal = document.getElementById('retaceoStatusModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
@endsection
