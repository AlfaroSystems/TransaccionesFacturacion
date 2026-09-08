@extends('layouts.app')
@section('title', isset($purchase_order) ? 'Editar Orden de Compra' : 'Nueva Orden de Compra')

@section('content')
@php
    $isEdit = isset($purchase_order) && $purchase_order->id_purchase_order;
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Header -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase_orders.index') }}" class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg hover:bg-teal-200 transition-colors">Órdenes de Compra</a>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">{{ $isEdit ? 'Editar Orden' : 'Nueva Orden' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                {{ $isEdit ? 'Editar Orden: ' . ($purchase_order->purchase_order_code ?? 'OC-'.$purchase_order->id_purchase_order) : 'Nueva Orden de Compra' }}
            </h1>
            <p class="text-slate-500 text-xs mt-1">
                {{ $isEdit ? 'Modifique los campos requeridos y guarde los cambios.' : 'Registre una nueva orden de compra o importe una cotización aprobada.' }}
            </p>
        </div>
        <a href="{{ $isEdit ? route('purchase_orders.show', $purchase_order->id_purchase_order) : route('purchase_orders.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Cancelar y Volver</span>
        </a>
    </div>

    <!-- Alert de Errores de Validación -->
    @if ($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl text-xs font-semibold space-y-1 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-sm text-rose-800">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Por favor corrija los errores en el formulario:</span>
            </div>
            <ul class="list-disc pl-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="purchaseOrderForm" action="{{ $isEdit ? route('purchase_orders.update', $purchase_order->id_purchase_order) : route('purchase_orders.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Importar Cotización (Solo en Crear) --}}
        @if(!$isEdit)
            <div class="bg-indigo-50/80 border border-indigo-200 rounded-2xl p-5 space-y-3 shadow-sm">
                <div>
                    <h3 class="font-extrabold text-indigo-900 text-xs uppercase tracking-wider flex items-center gap-2">
                        <span>⚡ Importar Cotización Aprobada</span>
                    </h3>
                    <p class="text-xs text-indigo-700 mt-0.5">
                        Seleccione una cotización aprobada para autocompletar proveedor, productos, precios e impuestos.
                    </p>
                </div>
                <div class="w-full">
                    <select id="quotationSelect" name="id_purchase_quotation" class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-200 bg-white text-sm font-semibold text-slate-800 focus:ring-2 focus:ring-indigo-500 outline-none shadow-sm">
                        <option value="">-- Seleccionar cotización aprobada --</option>
                        @php $qList = $quotations ?? $purchase_quotations ?? []; @endphp
                        @foreach($qList as $quotation)
                            <option value="{{ $quotation->id_purchase_quotation }}" {{ old('id_purchase_quotation', $purchase_order->id_purchase_quotation ?? '') == $quotation->id_purchase_quotation ? 'selected' : '' }}>
                                {{ $quotation->purchase_quotation_code ?? $quotation->quotation_code ?? ('Cotización #'.$quotation->id_purchase_quotation) }} {{ $quotation->supplier ? '- '.$quotation->supplier->name : '' }} (${{ number_format($quotation->total, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @endif

        <!-- Datos de Encabezado -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-5">
            <h3 class="text-base font-extrabold text-slate-800 border-b border-slate-100 pb-3 flex items-center gap-2">
                <span>📋 Datos Principales de la Orden</span>
            </h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-5">
                {{-- PROVEEDOR --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Proveedor *</label>
                    <select name="id_supplier" id="id_supplier" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Seleccione proveedor --</option>
                        @foreach($suppliers as $supplier)
                            <option value="{{ $supplier->id_supplier ?? $supplier->id }}" {{ old('id_supplier', $purchase_order->id_supplier ?? '') == ($supplier->id_supplier ?? $supplier->id) ? 'selected' : '' }}>
                                {{ $supplier->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- SUCURSAL --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Sucursal Destino *</label>
                    <select name="id_branch" id="id_branch" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Seleccione sucursal --</option>
                        @foreach($branches as $branch)
                            <option value="{{ $branch->id }}" {{ old('id_branch', $purchase_order->id_branch ?? '') == $branch->id ? 'selected' : '' }}>
                                {{ $branch->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- BODEGA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Bodega Recepción *</label>
                    <select name="id_warehouse" id="id_warehouse" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Seleccione bodega --</option>
                        @foreach($warehouses as $warehouse)
                            <option value="{{ $warehouse->id }}" {{ old('id_warehouse', $purchase_order->id_warehouse ?? '') == $warehouse->id ? 'selected' : '' }}>
                                {{ $warehouse->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- FECHA ORDEN --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha de Orden *</label>
                    <input type="datetime-local" name="order_date" value="{{ old('order_date', isset($purchase_order->order_date) ? $purchase_order->order_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- FECHA ESPERADA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Esperada de Entrega *</label>
                    <input type="datetime-local" name="expected_date" id="expected_date" value="{{ old('expected_date', isset($purchase_order->expected_date) ? $purchase_order->expected_date->format('Y-m-d\TH:i') : now()->addDays(7)->format('Y-m-d\TH:i')) }}" required class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- MONEDA --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Moneda *</label>
                    <select name="currency" id="currency" class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="USD" selected>USD - Dólares</option>
                    </select>
                </div>

                {{-- CONDICIONES DE PAGO --}}
                <div class="md:col-span-2">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Condiciones de Pago *</label>
                    <input type="text" name="payment_terms" id="payment_terms" value="{{ old('payment_terms', $purchase_order->payment_terms ?? 'Contrapago a 15 días netos') }}" required placeholder="Ejemplo: Crédito 30 días, Contado..." class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- NOTAS / OBSERVACIONES --}}
                <div class="md:col-span-3">
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Notas / Observaciones</label>
                    <textarea name="notes" rows="2" placeholder="Escriba información logística o instrucciones de entrega adicionales..." class="w-full rounded-xl border-slate-200 text-sm font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">{{ old('notes', $purchase_order->notes ?? '') }}</textarea>
                </div>
            </div>
        </div>

        <!-- Tabla de Productos -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">Productos de la Orden</h3>
                    <p class="text-xs text-slate-400">Especifique los artículos, cantidades y precios acordados.</p>
                </div>
                <button type="button" onclick="agregarProducto()" class="px-4 py-2.5 bg-[#005e66] hover:bg-[#00474f] text-white rounded-xl font-bold text-xs transition-all flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Agregar Producto</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse" id="productsTable">
                    <thead>
                        <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3">Producto</th>
                            <th class="py-3 px-3 text-center w-28">Cantidad</th>
                            <th class="py-3 px-3 text-center w-36">Unidad</th>
                            <th class="py-3 px-3 text-right w-32">P. Unitario ($)</th>
                            <th class="py-3 px-3 text-right w-28">Desc. ($)</th>
                            <th class="py-3 px-3 text-center w-24">IVA (%)</th>
                            <th class="py-3 px-3 text-right w-32">Total ($)</th>
                            <th class="py-3 px-3 text-center w-12">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="productsTbody" class="divide-y divide-slate-100">
                        {{-- Se llena vía JS --}}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabla de Gastos Adicionales -->
        <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 border-b border-slate-100 pb-4">
                <div>
                    <h3 class="text-base font-extrabold text-slate-800">Gastos Adicionales (Opcional)</h3>
                    <p class="text-xs text-slate-400">Registre fletes, seguros o aranceles asociados.</p>
                </div>
                <button type="button" onclick="agregarGasto()" class="px-4 py-2 bg-amber-500 hover:bg-amber-600 text-white rounded-xl font-bold text-xs transition-all flex items-center gap-1.5 shadow-sm">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Agregar Gasto</span>
                </button>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-sm text-left border-collapse" id="expensesTable">
                    <thead>
                        <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-100">
                            <th class="py-3 px-3">Tipo de Gasto</th>
                            <th class="py-3 px-3">Descripción</th>
                            <th class="py-3 px-3 text-right w-36">Monto ($)</th>
                            <th class="py-3 px-3 text-center w-12">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="expensesTbody" class="divide-y divide-slate-100">
                        {{-- Se llena vía JS --}}
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Resumen de Totales y Botones de Envío -->
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6 items-start">
            <div class="lg:col-span-2"></div>
            <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-6 space-y-3 text-sm">
                <h4 class="font-extrabold text-slate-800 text-sm border-b border-slate-100 pb-2">Resumen Financiero</h4>
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-bold text-slate-800" id="resumenSubtotal">$0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Descuento Total:</span>
                    <span class="font-bold text-rose-600" id="resumenDescuento">-$0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>IVA Total:</span>
                    <span class="font-bold text-slate-800" id="resumenIva">$0.00</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Gastos Adicionales:</span>
                    <span class="font-bold text-amber-600" id="resumenGastos">+$0.00</span>
                </div>
                <div class="pt-3 border-t border-slate-100 flex justify-between items-center">
                    <span class="text-base font-extrabold text-slate-800">Total General:</span>
                    <span class="text-xl font-extrabold text-[#005e66]" id="resumenTotal">$0.00</span>
                </div>

                <div class="pt-4 flex flex-col gap-2">
                    <button type="submit" class="w-full py-3 bg-[#005e66] hover:bg-[#00474f] text-white font-extrabold text-sm rounded-xl shadow-lg transition-all transform active:scale-98 flex items-center justify-center gap-2">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                        <span>{{ $isEdit ? 'Guardar Cambios' : 'Guardar Orden de Compra' }}</span>
                    </button>
                    <a href="{{ $isEdit ? route('purchase_orders.show', $purchase_order->id_purchase_order) : route('purchase_orders.index') }}" class="w-full py-2.5 bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold text-xs rounded-xl transition-all text-center">
                        Cancelar
                    </a>
                </div>
            </div>
        </div>
    </form>
</div>

<!-- Lógica JS para Productos y Gastos Dinámicos -->
<script>
    const productsData = @json($products);
    const unitsData = @json($units);
    const expenseTypesData = @json($expenseTypes);

    let productRowCount = 0;
    let expenseRowCount = 0;

    // Cargar datos existentes al Editar o Cargar Cotización
    const initialDetails = @json($isEdit ? $purchase_order->details : []);
    const initialExpenses = @json($isEdit ? $purchase_order->expenses : []);

    document.addEventListener('DOMContentLoaded', () => {
        if (initialDetails && initialDetails.length > 0) {
            initialDetails.forEach(detail => {
                agregarProducto(detail);
            });
        } else {
            @if(!$isEdit)
                agregarProducto();
            @endif
        }

        if (initialExpenses && initialExpenses.length > 0) {
            initialExpenses.forEach(expense => {
                agregarGasto(expense);
            });
        }

        recalcularTotales();

        // Autocompletar por Cotización
        const qSelect = document.getElementById('quotationSelect');
        if (qSelect) {
            qSelect.addEventListener('change', async function() {
                const qId = this.value;
                if (!qId) return;
                
                try {
                    const res = await fetch(`/purchase_orders/quotation-data/${qId}`);
                    if (!res.ok) return;
                    const data = await res.json();

                    if (data.id_supplier) document.getElementById('id_supplier').value = data.id_supplier;
                    if (data.id_branch) document.getElementById('id_branch').value = data.id_branch;
                    if (data.id_warehouse) document.getElementById('id_warehouse').value = data.id_warehouse;
                    if (data.payment_terms) document.getElementById('payment_terms').value = data.payment_terms;

                    // Limpiar productos e importar
                    document.getElementById('productsTbody').innerHTML = '';
                    productRowCount = 0;

                    if (data.details && data.details.length > 0) {
                        data.details.forEach(d => {
                            agregarProducto({
                                id_product: d.id_product,
                                quantity: d.quantity,
                                id_unit: d.id_unit,
                                unit_price: d.unit_price,
                                discount: d.discount,
                                tax_rate: d.tax_rate ?? 13
                            });
                        });
                    }
                    recalcularTotales();
                } catch (e) {
                    console.error("Error al importar cotización:", e);
                }
            });
        }
    });

    function agregarProducto(data = null) {
        productRowCount++;
        const index = productRowCount;
        const tbody = document.getElementById('productsTbody');

        const tr = document.createElement('tr');
        tr.id = `productRow_${index}`;
        tr.className = 'hover:bg-slate-50/50 transition-colors';

        let pOptions = '<option value="">-- Seleccionar --</option>';
        productsData.forEach(p => {
            const selected = (data && data.id_product == p.id_product) ? 'selected' : '';
            pOptions += `<option value="${p.id_product}" ${selected}>${p.name}</option>`;
        });

        let uOptions = '<option value="">-- Unidad --</option>';
        unitsData.forEach(u => {
            const selected = (data && data.id_unit == u.id_unit) ? 'selected' : '';
            uOptions += `<option value="${u.id_unit}" ${selected}>${u.name}</option>`;
        });

        const qty = data ? data.quantity : 1;
        const price = data ? data.unit_price : 0;
        const disc = data ? data.discount : 0;
        const taxRate = data ? (data.tax_rate !== undefined ? data.tax_rate : 13) : 13;

        tr.innerHTML = `
            <td class="py-2.5 px-3">
                <select name="products[${index}][id_product]" required onchange="onProductSelectChange(this, ${index})" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 focus:border-[#005e66] focus:ring-[#005e66]">
                    ${pOptions}
                </select>
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.01" min="0.01" name="products[${index}][quantity]" value="${qty}" required oninput="recalcularTotales()" class="w-full rounded-xl border-slate-200 text-xs text-center font-bold text-slate-800 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3">
                <select name="products[${index}][id_unit]" required class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-700">
                    ${uOptions}
                </select>
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.0001" min="0" name="products[${index}][unit_price]" value="${price}" required oninput="recalcularTotales()" class="w-full rounded-xl border-slate-200 text-xs text-right font-semibold text-slate-800 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.0001" min="0" name="products[${index}][discount]" value="${disc}" oninput="recalcularTotales()" class="w-full rounded-xl border-slate-200 text-xs text-right font-semibold text-rose-600 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.01" min="0" name="products[${index}][tax_rate]" value="${taxRate}" oninput="recalcularTotales()" class="w-full rounded-xl border-slate-200 text-xs text-center font-semibold text-slate-700 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3 text-right font-extrabold text-[#005e66] text-xs" id="lineTotal_${index}">
                $0.00
            </td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" onclick="eliminarFila('productRow_${index}')" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Eliminar fila">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        recalcularTotales();
    }

    function onProductSelectChange(selectEl, index) {
        const pId = selectEl.value;
        if (!pId) return;
        const prod = productsData.find(p => p.id_product == pId);
        if (prod) {
            const row = document.getElementById(`productRow_${index}`);
            if (row) {
                const priceInput = row.querySelector(`input[name="products[${index}][unit_price]"]`);
                if (priceInput && (!priceInput.value || parseFloat(priceInput.value) === 0)) {
                    priceInput.value = prod.purchase_price ?? prod.price ?? 0;
                }
                const unitSelect = row.querySelector(`select[name="products[${index}][id_unit]"]`);
                if (unitSelect && prod.id_unit) {
                    unitSelect.value = prod.id_unit;
                }
            }
        }
        recalcularTotales();
    }

    function agregarGasto(data = null) {
        expenseRowCount++;
        const index = expenseRowCount;
        const tbody = document.getElementById('expensesTbody');

        const tr = document.createElement('tr');
        tr.id = `expenseRow_${index}`;
        tr.className = 'hover:bg-slate-50/50 transition-colors';

        let eOptions = '<option value="">-- Tipo Gasto --</option>';
        expenseTypesData.forEach(et => {
            const selected = (data && data.id_expense_type == et.id_expense_type) ? 'selected' : '';
            const descAttr = (et.description || et.name).replace(/"/g, '&quot;');
            eOptions += `<option value="${et.id_expense_type}" data-description="${descAttr}" ${selected}>${et.name}</option>`;
        });

        const desc = data ? data.description : '';
        const amt = data ? data.amount : 0;

        tr.innerHTML = `
            <td class="py-2.5 px-3">
                <select name="expenses[${index}][id_expense_type]" required onchange="onExpenseTypeChange(this, ${index})" class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 focus:border-[#005e66]">
                    ${eOptions}
                </select>
            </td>
            <td class="py-2.5 px-3">
                <input type="text" name="expenses[${index}][description]" id="expenseDesc_${index}" value="${desc}" required placeholder="Descripción del gasto..." class="w-full rounded-xl border-slate-200 text-xs font-semibold text-slate-800 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3">
                <input type="number" step="0.0001" min="0" name="expenses[${index}][amount]" value="${amt}" required oninput="recalcularTotales()" class="w-full rounded-xl border-slate-200 text-xs text-right font-semibold text-amber-600 focus:border-[#005e66]">
            </td>
            <td class="py-2.5 px-3 text-center">
                <button type="button" onclick="eliminarFila('expenseRow_${index}')" class="p-1.5 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-colors" title="Eliminar gasto">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                </button>
            </td>
        `;
        tbody.appendChild(tr);
        recalcularTotales();
    }

    function onExpenseTypeChange(selectEl, index) {
        const descInput = document.getElementById(`expenseDesc_${index}`);
        const selectedOpt = selectEl.options[selectEl.selectedIndex];
        if (descInput && selectedOpt && selectedOpt.value) {
            const fullDesc = selectedOpt.getAttribute('data-description');
            descInput.value = fullDesc || selectedOpt.text;
        }
    }

    function eliminarFila(rowId) {
        const row = document.getElementById(rowId);
        if (row) {
            row.remove();
            recalcularTotales();
        }
    }

    function recalcularTotales() {
        let subtotal = 0;
        let discount = 0;
        let tax = 0;
        let addExpenses = 0;

        const productRows = document.querySelectorAll('#productsTbody tr');
        productRows.forEach(tr => {
            const qtyInput = tr.querySelector('input[name*="[quantity]"]');
            const priceInput = tr.querySelector('input[name*="[unit_price]"]');
            const discInput = tr.querySelector('input[name*="[discount]"]');
            const taxInput = tr.querySelector('input[name*="[tax_rate]"]');
            const lineTotalCell = tr.querySelector('td[id*="lineTotal_"]');

            if (qtyInput && priceInput) {
                const qty = parseFloat(qtyInput.value) || 0;
                const price = parseFloat(priceInput.value) || 0;
                const disc = parseFloat(discInput ? discInput.value : 0) || 0;
                const taxRate = parseFloat(taxInput ? taxInput.value : 0) || 0;

                const lineSubtotal = qty * price;
                const base = Math.max(0, lineSubtotal - disc);
                const taxAmt = base * (taxRate / 100);
                const lineTotal = base + taxAmt;

                subtotal += lineSubtotal;
                discount += disc;
                tax += taxAmt;

                if (lineTotalCell) {
                    lineTotalCell.textContent = `$${lineTotal.toFixed(2)}`;
                }
            }
        });

        const expenseRows = document.querySelectorAll('#expensesTbody tr');
        expenseRows.forEach(tr => {
            const amtInput = tr.querySelector('input[name*="[amount]"]');
            if (amtInput) {
                addExpenses += parseFloat(amtInput.value) || 0;
            }
        });

        const grandTotal = Math.max(0, subtotal - discount) + tax + addExpenses;

        document.getElementById('resumenSubtotal').textContent = `$${subtotal.toFixed(2)}`;
        document.getElementById('resumenDescuento').textContent = `-$${discount.toFixed(2)}`;
        document.getElementById('resumenIva').textContent = `$${tax.toFixed(2)}`;
        document.getElementById('resumenGastos').textContent = `+$${addExpenses.toFixed(2)}`;
        document.getElementById('resumenTotal').textContent = `$${grandTotal.toFixed(2)}`;
    }
</script>
@endsection
