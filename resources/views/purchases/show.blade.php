@extends('layouts.app')
@section('title', 'Detalle de Factura de Compra')

@section('content')
@php
    $statusClasses = [
        'draft'     => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600',
        'received'  => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800',
        'completed' => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
        'cancelled' => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800',
    ];
    $statusNames = [
        'draft'     => 'Borrador',
        'received'  => 'Recibida',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ];
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado con Botones de Acción -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg hover:bg-teal-200 transition-colors">
                    Compras
                </a>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">Detalle de Compra</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight font-mono">
                    {{ $purchase->purchase_code }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $statusClasses[$purchase->status] ?? 'bg-slate-100 text-slate-700' }}">
                    ● {{ $statusNames[$purchase->status] ?? ucfirst($purchase->status) }}
                </span>
                @if($purchase->supplier_invoice_number)
                    <span class="px-3 py-1 rounded-full text-xs font-bold bg-amber-50 text-amber-800 border border-amber-200 dark:bg-amber-950/50 dark:text-amber-300 dark:border-amber-800">
                        📄 Factura No. {{ $purchase->supplier_invoice_number }}
                    </span>
                @endif
            </div>
            <p class="text-slate-500 text-xs mt-1">
                Registrada el {{ $purchase->created_at ? $purchase->created_at->format('d/m/Y \a \l\a\s h:i A') : '-' }} por {{ $purchase->user->name ?? 'Sistema' }}
            </p>
        </div>

        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            {{-- Transiciones de Estado --}}
            @can('purchases.cambiar_estado')
                @if($purchase->status === 'draft')
                    <button type="button" onclick="openStatusModal('{{ route('purchases.updateStatus', $purchase->id_purchase) }}', 'received', '¿Marcar Compra como Recibida?', 'Se confirmará la recepción física de los productos.', 'Sí, marcar recibida', 'sky')" class="px-5 py-2.5 rounded-full bg-sky-600 hover:bg-sky-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <span>📦 Marcar Recibida</span>
                    </button>
                    <button type="button" onclick="openStatusModal('{{ route('purchases.updateStatus', $purchase->id_purchase) }}', 'completed', '¿Completar Compra?', 'Se confirmará la recepción definitiva y conciliación de factura.', 'Sí, completar', 'emerald')" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Completar</span>
                    </button>
                @elseif($purchase->status === 'received')
                    <button type="button" onclick="openStatusModal('{{ route('purchases.updateStatus', $purchase->id_purchase) }}', 'completed', '¿Completar Compra?', 'Se confirmará la recepción definitiva y conciliación de factura.', 'Sí, completar', 'emerald')" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>Completar</span>
                    </button>
                @endif

                @if(in_array($purchase->status, ['draft', 'received']))
                    <button type="button" onclick="openStatusModal('{{ route('purchases.updateStatus', $purchase->id_purchase) }}', 'cancelled', '¿Anular Factura de Compra?', 'Esta acción no se puede deshacer y la compra quedará cancelada.', 'Sí, anular compra', 'rose')" class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        <span>Anular</span>
                    </button>
                @endif
            @endcan

            @if($purchase->status === 'draft')
                @can('purchases.editar')
                    <a href="{{ route('purchases.edit', $purchase->id_purchase) }}" class="px-5 py-2.5 rounded-full bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md" title="Editar Compra">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <span>Editar</span>
                    </a>
                @endcan
            @can('retaceos.crear')
                <a href="{{ route('retaceos.create', ['id_purchase' => $purchase->id_purchase]) }}" class="px-5 py-2.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md" title="Prorratear y calcular costos de importación">
                    <span>📊 Calcular Retaceo</span>
                </a>
            @endcan

            <button type="button" onclick="window.print()" class="px-5 py-2.5 rounded-full bg-[#005e66] hover:bg-[#00474f] text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Imprimir</span>
            </button>

            <a href="{{ route('purchases.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs transition-all flex items-center justify-center gap-2 shadow-md">
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

    <!-- Tarjetas de Información General -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Tarjeta Factura y Recepción -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">🧾</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Datos de Factura</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between">
                    <span class="text-slate-400">No. Factura:</span>
                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ $purchase->supplier_invoice_number ?? 'No indicada' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Fecha Factura:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('d/m/Y') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Fecha Recepción:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y h:i A') : 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Moneda:</span>
                    <span class="font-mono font-bold text-slate-800 dark:text-slate-200">{{ $purchase->currency }}</span>
                </div>
            </div>
        </div>

        <!-- Tarjeta Proveedor -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">🏢</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Proveedor</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div>
                    <span class="text-slate-400 block">Razón Social / Nombre:</span>
                    <span class="font-extrabold text-sm text-slate-800 dark:text-slate-100">{{ $purchase->supplier->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Email:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->supplier->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Teléfono:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->supplier->phone ?? 'N/A' }}</span>
                </div>
            </div>
        </div>

        <!-- Tarjeta Orden de Compra y Destino -->
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-5 space-y-3">
            <div class="flex items-center gap-2 border-b border-slate-100 dark:border-slate-700 pb-2">
                <span class="text-teal-600 text-lg">📦</span>
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">Orden y Destino</h3>
            </div>
            <div class="space-y-2 text-xs">
                <div class="flex justify-between items-center">
                    <span class="text-slate-400">Orden de Compra:</span>
                    @if($purchase->purchaseOrder)
                        <a href="{{ route('purchase_orders.show', $purchase->purchaseOrder->id_purchase_order) }}" class="font-mono font-bold text-sky-600 dark:text-sky-400 hover:underline">
                            {{ $purchase->purchaseOrder->purchase_order_code }}
                        </a>
                    @else
                        <span class="text-slate-400">N/A</span>
                    @endif
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Sucursal:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->branch->name ?? 'Principal' }}</span>
                </div>
                <div class="flex justify-between">
                    <span class="text-slate-400">Bodega:</span>
                    <span class="font-semibold text-slate-700 dark:text-slate-300">{{ $purchase->warehouse->name ?? 'General' }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos de la Compra -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                <span>📋 Productos Recibidos</span>
            </h3>
            <span class="text-xs font-semibold text-slate-400">{{ $purchase->details->count() }} producto(s) en factura</span>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left text-xs">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100 dark:border-slate-700">
                    <tr>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4 text-center">Cant. Pedida</th>
                        <th class="py-3 px-4 text-center">Cant. Recibida</th>
                        <th class="py-3 px-4 text-right">Precio Unit.</th>
                        <th class="py-3 px-4 text-right">Descuento</th>
                        <th class="py-3 px-4 text-center">% IVA</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">Impuesto</th>
                        <th class="py-3 px-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @foreach($purchase->details as $item)
                        <tr class="hover:bg-slate-50/50 dark:hover:bg-slate-700/20">
                            <td class="py-3.5 px-4">
                                <div class="font-bold text-slate-800 dark:text-slate-200">{{ $item->product->name ?? 'Producto #'.$item->id_product }}</div>
                                <div class="text-[11px] text-slate-400 font-mono">{{ $item->unit->name ?? 'Unidad' }} {{ $item->product?->code ? '• '.$item->product->code : '' }}</div>
                                @if($item->notes)
                                    <div class="text-[11px] text-slate-500 italic mt-0.5">{{ $item->notes }}</div>
                                @endif
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-semibold text-slate-500">
                                {{ number_format($item->quantity_ordered, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono font-bold text-emerald-600 dark:text-emerald-400">
                                {{ number_format($item->quantity_received, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->unit_price, 4) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-rose-500">
                                -${{ number_format($item->discount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-mono text-slate-600 dark:text-slate-400">
                                {{ number_format($item->tax_rate, 2) }}%
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->subtotal, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono text-slate-700 dark:text-slate-300">
                                ${{ number_format($item->tax_amount, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-mono font-extrabold text-slate-800 dark:text-slate-100">
                                ${{ number_format($item->total, 2) }}
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>

        <!-- Desglose y Resumen Financiero -->
        <div class="flex flex-col md:flex-row justify-between items-start pt-4 border-t border-slate-100 dark:border-slate-700 gap-4">
            <div class="w-full md:w-1/2">
                @if($purchase->notes)
                    <div class="bg-slate-50 dark:bg-slate-900/40 rounded-xl p-4 border border-slate-200 dark:border-slate-700 text-xs">
                        <span class="font-bold text-slate-500 dark:text-slate-400 block mb-1">Notas de la compra:</span>
                        <p class="text-slate-700 dark:text-slate-300 whitespace-pre-line">{{ $purchase->notes }}</p>
                    </div>
                @endif
            </div>
            <div class="w-full md:w-80 space-y-2 text-sm bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Subtotal:</span>
                    <span class="font-mono font-bold">${{ number_format($purchase->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Descuentos:</span>
                    <span class="font-mono font-bold text-rose-500">-${{ number_format($purchase->discount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600 dark:text-slate-400">
                    <span>Impuestos (IVA):</span>
                    <span class="font-mono font-bold">${{ number_format($purchase->tax, 2) }}</span>
                </div>
                <div class="flex justify-between text-base font-extrabold text-slate-800 dark:text-slate-100 pt-2 border-t border-slate-200 dark:border-slate-700">
                    <span>Total Factura:</span>
                    <span class="font-mono text-[#005e66] dark:text-teal-400 text-xl">${{ number_format($purchase->total, 2) }} {{ $purchase->currency }}</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal de Cambio de Estado -->
<div id="statusModal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-6 max-w-md w-full shadow-2xl text-center relative mx-4">
        <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2" id="statusModalTitle">¿Cambiar Estado?</h3>
        <p class="text-slate-500 dark:text-slate-400 text-xs mb-6" id="statusModalDesc"></p>
        <form id="statusModalForm" method="POST" action="">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" id="statusModalInput" value="">
            <div class="flex justify-center gap-3">
                <button type="button" onclick="closeStatusModal()" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-xs transition-all">Cancelar</button>
                <button type="submit" id="statusModalBtn" class="px-5 py-2.5 bg-[#005e66] hover:bg-[#00474f] text-white font-semibold rounded-xl text-xs transition-all shadow-sm">Confirmar</button>
            </div>
        </form>
    </div>
</div>

<script>
    function openStatusModal(url, status, title, desc, btnText, color) {
        document.getElementById('statusModalForm').action = url;
        document.getElementById('statusModalInput').value = status;
        document.getElementById('statusModalTitle').textContent = title;
        document.getElementById('statusModalDesc').textContent = desc;
        const btn = document.getElementById('statusModalBtn');
        btn.textContent = btnText;

        const modal = document.getElementById('statusModal');
        modal.classList.remove('hidden');
        modal.style.display = 'flex';
    }

    function closeStatusModal() {
        const modal = document.getElementById('statusModal');
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
</script>
@endsection
