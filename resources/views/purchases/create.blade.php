@extends('layouts.app')
@section('title', isset($purchase) ? 'Editar Compra' : 'Registrar Compra / Factura')

@section('content')
@php
    $isEdit = isset($purchase) && $purchase->id_purchase;
    $selectedOrderId = old('id_purchase_order', $purchase->id_purchase_order ?? request('id_purchase_order'));
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchases.index') }}" class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg hover:bg-teal-200 transition-colors">
                    Compras
                </a>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">{{ $isEdit ? 'Editar Compra' : 'Nueva Factura / Recepción' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                {{ $isEdit ? 'Editar Compra: ' . $purchase->purchase_code : 'Registrar Compra' }}
            </h1>
            <p class="text-slate-500 text-xs mt-1">
                {{ $isEdit ? 'Modifique los datos de la compra en borrador.' : 'Seleccione una Orden de Compra aprobada para importar sus productos y registrar la factura del proveedor.' }}
            </p>
        </div>
        <a href="{{ $isEdit ? route('purchases.show', $purchase->id_purchase) : route('purchases.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Cancelar y Volver</span>
        </a>
    </div>

    <!-- Alert de Errores -->
    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-5 py-4 rounded-2xl text-xs font-semibold space-y-1 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-sm text-rose-800 dark:text-rose-200">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Por favor revise los errores en el formulario:</span>
            </div>
            <ul class="list-disc pl-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="purchaseForm" action="{{ $isEdit ? route('purchases.update', $purchase->id_purchase) : route('purchases.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Seleccionar Orden de Compra --}}
        @if(!$isEdit)
            <div class="bg-indigo-50/80 dark:bg-slate-800/90 border border-indigo-200 dark:border-slate-700 rounded-2xl p-5 space-y-3 shadow-sm">
                <div>
                    <h3 class="font-extrabold text-indigo-900 dark:text-indigo-300 text-xs uppercase tracking-wider flex items-center gap-2">
                        <span>📋 Seleccionar Orden de Compra *</span>
                    </h3>
                    <p class="text-xs text-indigo-700 dark:text-slate-400 mt-0.5">
                        Elija la orden de compra emitida a la que corresponde la factura y mercancía recibida.
                    </p>
                </div>
                <div>
                    <select id="orderSelect" name="id_purchase_order" required class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66] outline-none shadow-sm">
                        <option value="">-- Seleccione una orden de compra --</option>
                        @foreach($orders as $ord)
                            <option value="{{ $ord->id_purchase_order }}" {{ (string)$selectedOrderId === (string)$ord->id_purchase_order ? 'selected' : '' }}>
                                {{ $ord->purchase_order_code }} - {{ $ord->supplier->name ?? 'Sin Proveedor' }} (${{ number_format($ord->total, 2) }}) [Estado: {{ ucfirst($ord->status) }}]
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" name="id_purchase_order" value="{{ $purchase->id_purchase_order }}">
            <div class="bg-slate-100 dark:bg-slate-800 rounded-xl p-4 text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span>Orden de Compra Vinculada: <strong class="font-mono text-sm text-[#005e66] dark:text-teal-400">{{ $purchase->purchaseOrder->purchase_order_code ?? 'OC-'.$purchase->id_purchase_order }}</strong></span>
                <span>Proveedor: <strong>{{ $purchase->supplier->name ?? 'N/A' }}</strong></span>
            </div>
        @endif

        {{-- Datos de Cabecera de Compra --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 border-b border-slate-100 dark:border-slate-700 pb-3 flex items-center gap-2">
                <span>🧾 Datos de la Factura y Recepción</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- NÚMERO DE FACTURA COMERCIAL --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">No. Factura Proveedor</label>
                    <input type="text" name="supplier_invoice_number" id="supplier_invoice_number" value="{{ old('supplier_invoice_number', $purchase->supplier_invoice_number ?? '') }}" placeholder="Ej: FAC-001-987" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- FECHA DE LA FACTURA PROVEEDOR --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Emisión Factura</label>
                    <input type="date" name="supplier_invoice_date" id="supplier_invoice_date" value="{{ old('supplier_invoice_date', isset($purchase->supplier_invoice_date) ? $purchase->supplier_invoice_date->format('Y-m-d') : '') }}" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- FECHA DE RECEPCIÓN / COMPRA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de Recepción *</label>
                    <input type="datetime-local" name="purchase_date" id="purchase_date" required value="{{ old('purchase_date', isset($purchase->purchase_date) ? $purchase->purchase_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- ESTADO --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado Inicial *</label>
                    <select name="status" id="status" required class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="completed" {{ old('status', $purchase->status ?? 'completed') === 'completed' ? 'selected' : '' }}>Completada</option>
                        <option value="received" {{ old('status', $purchase->status ?? '') === 'received' ? 'selected' : '' }}>Recibida</option>
                        <option value="draft" {{ old('status', $purchase->status ?? '') === 'draft' ? 'selected' : '' }}>Borrador</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- PROVEEDOR (HEREDADO / CONFIRMADO) --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Proveedor *</label>
                    <select name="id_supplier" id="id_supplier" required class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Seleccione proveedor --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id_supplier }}" {{ (string)old('id_supplier', $purchase->id_supplier ?? '') === (string)$supplier->id_supplier ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SUCURSAL --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sucursal</label>
                    <select name="id_branch" id="id_branch" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Sucursal --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ (string)old('id_branch', $purchase->id_branch ?? '') === (string)$branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- BODEGA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bodega Destino</label>
                    <select name="id_warehouse" id="id_warehouse" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Bodega --</option>
                        @foreach($warehouses as $wh)
                            <option value="{{ $wh->id }}" {{ (string)old('id_warehouse', $purchase->id_warehouse ?? '') === (string)$wh->id ? 'selected' : '' }}>
                                {{ $wh->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- MONEDA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Moneda</label>
                    <input type="text" name="currency" id="currency" value="{{ old('currency', $purchase->currency ?? 'USD') }}" maxlength="3" class="w-full uppercase rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Notas u Observaciones</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Observaciones sobre la recepción física o factura..." class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">{{ old('notes', $purchase->notes ?? '') }}</textarea>
            </div>
        </div>

        {{-- Tabla de Productos Recibidos --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                    <span>📦 Líneas de Productos a Recibir</span>
                </h3>
                <span class="text-xs text-slate-400">Verifique las cantidades recibidas y los importes de factura</span>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="itemsTable">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-3 w-1/4">Producto</th>
                            <th class="py-3 px-3 text-center">Cant. Pedida</th>
                            <th class="py-3 px-3 text-center w-28">Cant. Recibida *</th>
                            <th class="py-3 px-3 w-28">Precio Unit. *</th>
                            <th class="py-3 px-3 w-24">Desc. ($)</th>
                            <th class="py-3 px-3 w-20">% IVA</th>
                            <th class="py-3 px-3 text-right">Subtotal</th>
                            <th class="py-3 px-3 text-right">Impuesto</th>
                            <th class="py-3 px-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody id="itemsBody" class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @if($isEdit && isset($purchase->details))
                            @foreach($purchase->details as $index => $detail)
                                <tr class="item-row" data-index="{{ $index }}">
                                    <td class="py-3 px-3">
                                        <input type="hidden" name="details[{{ $index }}][id_purchase_order_detail]" value="{{ $detail->id_purchase_order_detail }}">
                                        <input type="hidden" name="details[{{ $index }}][id_product]" value="{{ $detail->id_product }}">
                                        <input type="hidden" name="details[{{ $index }}][id_unit]" value="{{ $detail->id_unit }}">
                                        <input type="hidden" name="details[{{ $index }}][quantity_ordered]" value="{{ $detail->quantity_ordered }}">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $detail->product->name ?? 'Producto' }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $detail->unit->name ?? 'Unidad' }}</div>
                                    </td>
                                    <td class="py-3 px-3 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                                        {{ number_format($detail->quantity_ordered, 2) }}
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0.0001" name="details[{{ $index }}][quantity_received]" value="{{ old('details.'.$index.'.quantity_received', (float)$detail->quantity_received) }}" required class="w-full px-2 py-1.5 text-center font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 qty-input">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0" name="details[{{ $index }}][unit_price]" value="{{ old('details.'.$index.'.unit_price', (float)$detail->unit_price) }}" required class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 price-input">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0" name="details[{{ $index }}][discount]" value="{{ old('details.'.$index.'.discount', (float)$detail->discount) }}" class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 discount-input">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.01" min="0" max="100" name="details[{{ $index }}][tax_rate]" value="{{ old('details.'.$index.'.tax_rate', (float)$detail->tax_rate) }}" class="w-full px-2 py-1.5 text-center font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 tax-input">
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono row-subtotal">$0.00</td>
                                    <td class="py-3 px-3 text-right font-mono row-tax">$0.00</td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 row-total">$0.00</td>
                                </tr>
                            @endforeach
                        @else
                            <tr id="emptyRow">
                                <td colspan="9" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Seleccione una orden de compra en la parte superior para cargar automáticamente los productos.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Resumen Financiero Total -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="w-full md:w-80 space-y-2 text-sm">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Subtotal:</span>
                        <span class="font-mono font-bold" id="lblSubtotal">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Descuentos:</span>
                        <span class="font-mono font-bold text-rose-500" id="lblDiscount">-$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Impuestos (IVA):</span>
                        <span class="font-mono font-bold" id="lblTax">$0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-slate-800 dark:text-slate-100 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span>Total a Pagar:</span>
                        <span class="font-mono text-[#005e66] dark:text-teal-400 text-lg" id="lblTotal">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('purchases.index') }}" class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                Cancelar
            </a>
            <button type="submit" id="btnGuardar" class="px-8 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white font-bold text-sm shadow-md transition-all transform hover:-translate-y-0.5">
                {{ $isEdit ? 'Actualizar Compra' : 'Registrar Compra' }}
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const orderSelect = document.getElementById('orderSelect');
        const itemsBody   = document.getElementById('itemsBody');

        // Autocargar si ya hay una orden seleccionada
        if (orderSelect && orderSelect.value) {
            cargarOrden(orderSelect.value);
        }

        if (orderSelect) {
            orderSelect.addEventListener('change', function () {
                if (this.value) {
                    cargarOrden(this.value);
                } else {
                    itemsBody.innerHTML = '<tr id="emptyRow"><td colspan="9" class="py-8 text-center text-slate-400">Seleccione una orden de compra para cargar productos.</td></tr>';
                    calcularTotales();
                }
            });
        }

        function cargarOrden(orderId) {
            itemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Cargando productos de la orden...</td></tr>';

            fetch(`/purchases/order-data/${orderId}`)
                .then(res => res.json())
                .then(data => {
                    // Completar datos de cabecera
                    if (data.id_supplier) document.getElementById('id_supplier').value = data.id_supplier;
                    if (data.id_branch) document.getElementById('id_branch').value = data.id_branch;
                    if (data.id_warehouse) document.getElementById('id_warehouse').value = data.id_warehouse;
                    if (data.currency) document.getElementById('currency').value = data.currency;

                    itemsBody.innerHTML = '';
                    if (!data.details || data.details.length === 0) {
                        itemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">La orden no tiene productos registrados.</td></tr>';
                        return;
                    }

                    data.details.forEach((d, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = 'item-row hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors';
                        tr.dataset.index = idx;

                        tr.innerHTML = `
                            <td class="py-3 px-3">
                                <input type="hidden" name="details[${idx}][id_purchase_order_detail]" value="${d.id_purchase_order_detail}">
                                <input type="hidden" name="details[${idx}][id_product]" value="${d.id_product}">
                                <input type="hidden" name="details[${idx}][id_unit]" value="${d.id_unit || ''}">
                                <input type="hidden" name="details[${idx}][quantity_ordered]" value="${d.quantity_ordered}">
                                <div class="font-bold text-slate-800 dark:text-slate-200">${d.product_name}</div>
                                <div class="text-[11px] text-slate-400 font-mono">${d.unit_name} ${d.product_code ? '• ' + d.product_code : ''}</div>
                                ${d.already_received > 0 ? `<div class="text-[10px] text-amber-600 font-semibold">Ya recibido: ${d.already_received}</div>` : ''}
                            </td>
                            <td class="py-3 px-3 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                                ${d.quantity_ordered}
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0.0001" name="details[${idx}][quantity_received]" value="${d.pending_quantity > 0 ? d.pending_quantity : d.quantity_ordered}" required class="w-full px-2 py-1.5 text-center font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 qty-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0" name="details[${idx}][unit_price]" value="${d.unit_price}" required class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 price-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0" name="details[${idx}][discount]" value="${d.discount || 0}" class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 discount-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.01" min="0" max="100" name="details[${idx}][tax_rate]" value="${d.tax_rate || 0}" class="w-full px-2 py-1.5 text-center font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 tax-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3 text-right font-mono row-subtotal">$0.00</td>
                            <td class="py-3 px-3 text-right font-mono row-tax">$0.00</td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 row-total">$0.00</td>
                        `;
                        itemsBody.appendChild(tr);
                    });

                    vincularEventosInputs();
                    calcularTotales();
                })
                .catch(err => {
                    console.error('Error al cargar datos de orden:', err);
                    itemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-rose-500">Error al cargar la orden de compra. Intente nuevamente.</td></tr>';
                });
        }

        function vincularEventosInputs() {
            document.querySelectorAll('.qty-input, .price-input, .discount-input, .tax-input').forEach(input => {
                input.removeEventListener('input', calcularTotales);
                input.addEventListener('input', calcularTotales);
            });
        }

        function calcularTotales() {
            let totalSubtotal = 0;
            let totalDiscount = 0;
            let totalTax      = 0;

            document.querySelectorAll('.item-row').forEach(row => {
                const qty      = parseFloat(row.querySelector('.qty-input')?.value) || 0;
                const price    = parseFloat(row.querySelector('.price-input')?.value) || 0;
                const discount = parseFloat(row.querySelector('.discount-input')?.value) || 0;
                const taxRate  = parseFloat(row.querySelector('.tax-input')?.value) || 0;

                const lineSubtotal = qty * price;
                const base         = Math.max(0, lineSubtotal - discount);
                const lineTax      = base * (taxRate / 100);
                const lineTotal    = base + lineTax;

                totalSubtotal += lineSubtotal;
                totalDiscount += discount;
                totalTax      += lineTax;

                const tdSub = row.querySelector('.row-subtotal');
                const tdTax = row.querySelector('.row-tax');
                const tdTot = row.querySelector('.row-total');

                if (tdSub) tdSub.textContent = '$' + lineSubtotal.toFixed(2);
                if (tdTax) tdTax.textContent = '$' + lineTax.toFixed(2);
                if (tdTot) tdTot.textContent = '$' + lineTotal.toFixed(2);
            });

            const grandTotal = Math.max(0, totalSubtotal - totalDiscount) + totalTax;

            document.getElementById('lblSubtotal').textContent = '$' + totalSubtotal.toFixed(2);
            document.getElementById('lblDiscount').textContent = '-$' + totalDiscount.toFixed(2);
            document.getElementById('lblTax').textContent      = '$' + totalTax.toFixed(2);
            document.getElementById('lblTotal').textContent    = '$' + grandTotal.toFixed(2);
        }

        // Ejecución inicial por si estamos en vista de edición
        vincularEventosInputs();
        calcularTotales();
    });
</script>
@endsection
