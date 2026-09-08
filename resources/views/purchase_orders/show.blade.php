@extends('layouts.app')
@section('title', 'Detalle de Orden de Compra')

@section('content')
@php
    $purchase_order = $purchase_order ?? $purchaseOrder ?? null;
    $statusClasses = [
        'draft' => 'bg-slate-100 text-slate-700 border-slate-200',
        'issued' => 'bg-sky-50 text-sky-700 border-sky-200',
        'partial_received' => 'bg-indigo-50 text-indigo-700 border-indigo-200',
        'completed' => 'bg-emerald-50 text-emerald-700 border-emerald-200',
        'cancelled' => 'bg-rose-50 text-rose-700 border-rose-200',
    ];
    $statusNames = [
        'draft' => 'Borrador',
        'issued' => 'Emitida',
        'partial_received' => 'Recepción Parcial',
        'completed' => 'Completada',
        'cancelled' => 'Cancelada',
    ];
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado con Botones de Acción -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg">Órdenes de Compra</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">Detalle</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight">
                    {{ $purchase_order->purchase_order_code ?? 'OC-' . $purchase_order->id_purchase_order }}
                </h1>
                <span class="px-3 py-1 rounded-full text-xs font-extrabold border {{ $statusClasses[$purchase_order->status] ?? 'bg-gray-100 text-gray-700 border-gray-200' }}">
                    ● {{ $statusNames[$purchase_order->status] ?? ucfirst($purchase_order->status) }}
                </span>
            </div>
            <p class="text-slate-500 text-xs mt-1">
                Registrada el {{ $purchase_order->created_at ? $purchase_order->created_at->format('d/m/Y \a \l\a\s h:i A') : 'N/A' }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <!-- Cambios de Estado -->
            @if($purchase_order->status === 'draft')
                <button type="button" onclick="openStatusModal('{{ route('purchase_orders.updateStatus', $purchase_order->id_purchase_order) }}', 'issued', '¿Emitir Orden de Compra?', 'Al emitirla, la orden quedará oficialmente enviada y ya no podrá ser modificada.', 'Sí, emitir orden', 'blue')" class="px-5 py-2.5 rounded-full bg-blue-600 hover:bg-blue-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                    <span>Emitir Orden</span>
                </button>
            @elseif($purchase_order->status === 'issued')
                <button type="button" onclick="openStatusModal('{{ route('purchase_orders.updateStatus', $purchase_order->id_purchase_order) }}', 'partial_received', '¿Registrar Recepción Parcial?', 'El estado cambiará a recepción parcial de productos.', 'Sí, registrar recepción', 'indigo')" class="px-5 py-2.5 rounded-full bg-indigo-600 hover:bg-indigo-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                    <span>📦 Recepción Parcial</span>
                </button>
                <button type="button" onclick="openStatusModal('{{ route('purchase_orders.updateStatus', $purchase_order->id_purchase_order) }}', 'completed', '¿Marcar Orden como Completada?', 'Se confirmará la recepción total de los productos de esta orden de compra.', 'Sí, marcar completada', 'emerald')" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Marcar Completada</span>
                </button>
            @elseif($purchase_order->status === 'partial_received')
                <button type="button" onclick="openStatusModal('{{ route('purchase_orders.updateStatus', $purchase_order->id_purchase_order) }}', 'completed', '¿Marcar Orden como Completada?', 'Se confirmará la recepción total de los productos de esta orden de compra.', 'Sí, marcar completada', 'emerald')" class="px-5 py-2.5 rounded-full bg-emerald-600 hover:bg-emerald-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Marcar Completada</span>
                </button>
            @endif

            @if(in_array($purchase_order->status, ['draft', 'issued', 'partial_received']))
                <button type="button" onclick="openStatusModal('{{ route('purchase_orders.updateStatus', $purchase_order->id_purchase_order) }}', 'cancelled', '¿Cancelar Orden de Compra?', 'Esta acción no se puede deshacer y la orden quedará anulada.', 'Sí, cancelar orden', 'rose')" class="px-5 py-2.5 rounded-full bg-rose-600 hover:bg-rose-700 text-white font-extrabold text-xs transition-all flex items-center gap-2 shadow-md">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    <span>Cancelar</span>
                </button>
            @endif

            @if(Route::has('purchase_orders.pdf'))
                <a href="{{ route('purchase_orders.pdf', $purchase_order->id_purchase_order) }}" target="_blank" class="px-5 py-2.5 rounded-full bg-[#005e66] hover:bg-[#00474f] text-white font-extrabold text-xs transition-all flex items-center justify-center gap-2 shadow-md" title="Descargar / Imprimir PDF">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <span>Imprimir PDF</span>
                </a>
            @endif

            @if($purchase_order->isEditable())
                <a href="{{ route('purchase_orders.edit', $purchase_order->id_purchase_order) }}" class="px-5 py-2.5 rounded-full bg-amber-500 hover:bg-amber-600 text-white font-extrabold text-xs transition-all flex items-center justify-center gap-2 shadow-md" title="Editar Orden">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                    </svg>
                    <span>Editar Orden</span>
                </a>
            @endif

            <a href="{{ route('purchase_orders.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-extrabold text-xs transition-all flex items-center justify-center gap-2 shadow-md">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Tarjetas de Información General y Proveedor -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Información de la Orden -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4 md:col-span-2">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 text-[#005e66] flex items-center justify-center text-lg font-bold">
                    📦
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800">Detalles de la Orden</h2>
                    <span class="text-xs text-slate-400">Información logística y de entrega</span>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Proveedor:</span>
                        <span class="font-bold text-slate-800">{{ $purchase_order->supplier->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Sucursal Destino:</span>
                        <span class="font-semibold text-slate-700">{{ $purchase_order->branch->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Bodega Recepción:</span>
                        <span class="font-semibold text-slate-700">{{ $purchase_order->warehouse->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Usuario Creador:</span>
                        <span class="font-medium text-slate-700">{{ $purchase_order->user->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="space-y-3">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Fecha Emisión:</span>
                        <span class="font-bold text-slate-700">
                            {{ $purchase_order->order_date ? $purchase_order->order_date->format('d/m/Y H:i') : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Fecha Esperada:</span>
                        <span class="font-bold text-[#005e66] bg-teal-50 px-2.5 py-0.5 rounded-lg border border-teal-100 text-xs">
                            {{ $purchase_order->expected_date ? $purchase_order->expected_date->format('d/m/Y H:i') : 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Condición Pago:</span>
                        <span class="font-semibold text-slate-700">{{ $purchase_order->payment_terms ?? 'Contado' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Cotización Origen:</span>
                        <span class="font-mono text-xs font-bold text-slate-600 bg-slate-100 px-2 py-0.5 rounded">
                            {{ $purchase_order->quotation->quotation_code ?? ($purchase_order->id_purchase_quotation ? '#'.$purchase_order->id_purchase_quotation : 'Ninguna') }}
                        </span>
                    </div>
                </div>
            </div>
            @if($purchase_order->notes)
                <div class="pt-3 border-t border-slate-100">
                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">Notas / Observaciones:</span>
                    <p class="text-xs text-slate-600 bg-slate-50 p-3 rounded-xl border border-slate-100 font-medium">
                        {{ $purchase_order->notes }}
                    </p>
                </div>
            @endif
        </div>

        <!-- Resumen Financiero -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 flex flex-col justify-between space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-3">
                <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-lg font-bold">
                    💵
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800">Resumen Financiero</h2>
                    <span class="text-xs text-slate-400">Totales en {{ $purchase_order->currency ?? 'USD' }}</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-500">Subtotal:</span>
                    <span class="font-bold text-slate-700">${{ number_format($purchase_order->subtotal, 4) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-500">Descuento:</span>
                    <span class="font-bold text-rose-600">-${{ number_format($purchase_order->discount, 4) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-500">IVA (13%):</span>
                    <span class="font-bold text-slate-700">${{ number_format($purchase_order->tax, 4) }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-semibold text-slate-500">Gastos Adicionales:</span>
                    <span class="font-bold text-amber-600">+${{ number_format($purchase_order->additional_expenses, 4) }}</span>
                </div>
                <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-sm font-extrabold text-slate-800">Total General:</span>
                    <span class="text-lg font-extrabold text-[#005e66]">${{ number_format($purchase_order->total, 4) }}</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Productos / Detalles -->
    <section class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
        <div class="flex items-center justify-between border-b border-slate-100 pb-4">
            <div class="flex items-center gap-3">
                <div class="w-9 h-9 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center font-bold">
                    🛍️
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">Productos de la Orden</h3>
                    <span class="text-xs text-slate-400">{{ $purchase_order->details->count() }} ítems registrados</span>
                </div>
            </div>
        </div>

        <div class="overflow-x-auto">
            <table class="w-full text-left border-collapse">
                <thead>
                    <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                        <th class="py-3 px-4">#</th>
                        <th class="py-3 px-4">Producto</th>
                        <th class="py-3 px-4 text-center">Cantidad</th>
                        <th class="py-3 px-4 text-center">Unidad</th>
                        <th class="py-3 px-4 text-right">P. Unitario</th>
                        <th class="py-3 px-4 text-right">Descuento</th>
                        <th class="py-3 px-4 text-right">Subtotal</th>
                        <th class="py-3 px-4 text-right">IVA</th>
                        <th class="py-3 px-4 text-right">Total</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 text-sm">
                    @forelse($purchase_order->details as $index => $detail)
                        <tr class="hover:bg-slate-50/60 transition-colors">
                            <td class="py-3.5 px-4 font-bold text-slate-400 text-xs">{{ $index + 1 }}</td>
                            <td class="py-3.5 px-4 font-bold text-slate-800">
                                {{ $detail->product->name ?? 'Producto #'.$detail->id_product }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-extrabold text-slate-700">
                                {{ number_format($detail->quantity, 2) }}
                            </td>
                            <td class="py-3.5 px-4 text-center font-medium text-slate-500 text-xs">
                                {{ $detail->unit->name ?? 'N/A' }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-slate-700">
                                ${{ number_format($detail->unit_price, 4) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-rose-600">
                                ${{ number_format($detail->discount, 4) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-slate-700">
                                ${{ number_format($detail->subtotal, 4) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-semibold text-slate-700">
                                ${{ number_format($detail->tax_amount, 4) }}
                            </td>
                            <td class="py-3.5 px-4 text-right font-extrabold text-[#005e66]">
                                ${{ number_format($detail->total, 4) }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="9" class="py-8 text-center text-slate-400 font-semibold text-xs">
                                No se encontraron productos asociados a esta orden.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </section>

    <!-- Gastos Adicionales (Si existen) -->
    @if($purchase_order->expenses && $purchase_order->expenses->count() > 0)
        <section class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 pb-4">
                <div class="w-9 h-9 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center font-bold">
                    🚚
                </div>
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">Gastos Adicionales Asignados</h3>
                    <span class="text-xs text-slate-400">Fletes, impuestos especiales, seguros, etc.</span>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left border-collapse">
                    <thead>
                        <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Tipo de Gasto</th>
                            <th class="py-3 px-4">Descripción</th>
                            <th class="py-3 px-4 text-right">Monto</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-100 text-sm">
                        @foreach($purchase_order->expenses as $gIndex => $expense)
                            <tr class="hover:bg-slate-50/60 transition-colors">
                                <td class="py-3 px-4 font-bold text-slate-400 text-xs">{{ $gIndex + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $expense->expenseType->name ?? 'Gasto #'.$expense->id_expense_type }}
                                </td>
                                <td class="py-3 px-4 text-slate-600 text-xs font-medium">
                                    {{ $expense->description ?? 'Sin descripción' }}
                                </td>
                                <td class="py-3 px-4 text-right font-extrabold text-amber-600">
                                    +${{ number_format($expense->amount, 4) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </section>
    @endif
</div>

<!-- Modal Personalizado para Confirmar Cambio de Estado -->
<div id="status-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 backdrop-blur-sm transition-all duration-200">
    <div class="bg-white dark:bg-slate-800 rounded-3xl p-8 max-w-md w-full shadow-2xl relative mx-4 text-center transform scale-95 transition-all duration-200 border border-slate-100 dark:border-slate-700">
        <button type="button" onclick="closeModal('status-modal')" class="absolute top-5 right-5 text-slate-400 hover:text-slate-600 transition-colors">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        
        <div id="status-icon-box" class="w-16 h-16 rounded-full border-2 border-sky-400 bg-sky-50 flex items-center justify-center mx-auto text-sky-600 mb-5">
            <svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        </div>

        <h3 id="status-modal-title" class="text-xl font-extrabold text-slate-800 dark:text-slate-100 tracking-tight">¿Confirmar Acción?</h3>
        <p id="status-modal-desc" class="text-slate-500 dark:text-slate-400 text-xs mt-2 font-medium leading-relaxed">
            Descripción de la confirmación...
        </p>

        <form id="status-modal-form" action="" method="POST" class="mt-6">
            @csrf
            @method('PATCH')
            <input type="hidden" name="status" id="status-modal-input" value="">

            <div class="flex items-center justify-center gap-3 pt-2">
                <button type="button" onclick="closeModal('status-modal')" class="px-6 py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 rounded-full font-bold text-xs transition-all">
                    Cancelar
                </button>
                <button type="submit" id="status-modal-submit" class="px-6 py-2.5 bg-sky-600 hover:bg-sky-700 text-white font-extrabold rounded-full text-xs transition-all shadow-md">
                    Confirmar
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function openStatusModal(actionUrl, newStatus, title, description, btnText, theme) {
        const modal = document.getElementById('status-modal');
        const form = document.getElementById('status-modal-form');
        const statusInput = document.getElementById('status-modal-input');
        const titleEl = document.getElementById('status-modal-title');
        const descEl = document.getElementById('status-modal-desc');
        const submitBtn = document.getElementById('status-modal-submit');
        const iconBox = document.getElementById('status-icon-box');

        form.action = actionUrl;
        statusInput.value = newStatus;
        titleEl.textContent = title;
        descEl.textContent = description;
        submitBtn.textContent = btnText;

        if (theme === 'blue' || theme === 'sky') {
            submitBtn.className = 'px-6 py-2.5 font-extrabold rounded-full text-xs transition-all shadow-md bg-blue-600 hover:bg-blue-700 text-white';
            iconBox.className = 'w-16 h-16 rounded-full border-2 border-blue-400 bg-blue-50 flex items-center justify-center mx-auto text-blue-600 mb-5';
            iconBox.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>';
        } else if (theme === 'indigo') {
            submitBtn.className = 'px-6 py-2.5 font-extrabold rounded-full text-xs transition-all shadow-md bg-indigo-600 hover:bg-indigo-700 text-white';
            iconBox.className = 'w-16 h-16 rounded-full border-2 border-indigo-400 bg-indigo-50 flex items-center justify-center mx-auto text-indigo-600 mb-5';
            iconBox.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
        } else if (theme === 'emerald') {
            submitBtn.className = 'px-6 py-2.5 font-extrabold rounded-full text-xs transition-all shadow-md bg-emerald-600 hover:bg-emerald-700 text-white';
            iconBox.className = 'w-16 h-16 rounded-full border-2 border-emerald-400 bg-emerald-50 flex items-center justify-center mx-auto text-emerald-600 mb-5';
            iconBox.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>';
        } else if (theme === 'rose') {
            submitBtn.className = 'px-6 py-2.5 font-extrabold rounded-full text-xs transition-all shadow-md bg-rose-600 hover:bg-rose-700 text-white';
            iconBox.className = 'w-16 h-16 rounded-full border-2 border-rose-400 bg-rose-50 flex items-center justify-center mx-auto text-rose-600 mb-5';
            iconBox.innerHTML = '<svg class="w-8 h-8" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/></svg>';
        }

        openModal('status-modal');
    }
</script>
@endsection