@extends('layouts.app')
@section('title', isset($retaceo) ? 'Editar Retaceo' : 'Calcular Retaceo de Importación')

@section('content')
@php
    $isEdit = isset($retaceo) && $retaceo->id_retaceo;
    $selectedPurchaseId = old('id_purchase', $retaceo->id_purchase ?? request('id_purchase'));
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('retaceos.index') }}" class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg hover:bg-teal-200 transition-colors">
                    Retaceos
                </a>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500 uppercase">{{ $isEdit ? 'Editar Retaceo' : 'Nueva Liquidación de Importación' }}</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                {{ $isEdit ? 'Editar Retaceo: ' . $retaceo->retaceo_code : 'Calcular Retaceo' }}
            </h1>
            <p class="text-slate-500 text-xs mt-1">
                {{ $isEdit ? 'Modifique los costos y gastos aduanales del retaceo.' : 'Seleccione una factura de compra exterior, ingrese flete y gastos de importación para prorratear los costos unitarios reales.' }}
            </p>
        </div>
        <a href="{{ $isEdit ? route('retaceos.show', $retaceo->id_retaceo) : route('retaceos.index') }}" class="px-5 py-2.5 rounded-full bg-slate-800 hover:bg-slate-700 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            <span>Cancelar y Volver</span>
        </a>
    </div>

    <!-- Alert de Errores -->
    @if ($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-5 py-4 rounded-2xl text-xs font-semibold space-y-1 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-sm text-rose-800 dark:text-rose-200">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Revise los errores en el formulario:</span>
            </div>
            <ul class="list-disc pl-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="retaceoForm" action="{{ $isEdit ? route('retaceos.update', $retaceo->id_retaceo) : route('retaceos.store') }}" method="POST" class="space-y-6">
        @csrf
        @if($isEdit)
            @method('PUT')
        @endif

        {{-- Selector de Compra --}}
        @if(!$isEdit)
            <div class="bg-indigo-50/80 dark:bg-slate-800/90 border border-indigo-200 dark:border-slate-700 rounded-2xl p-5 space-y-3 shadow-sm">
                <div>
                    <h3 class="font-extrabold text-indigo-900 dark:text-indigo-300 text-xs uppercase tracking-wider flex items-center gap-2">
                        <span>🧾 Seleccionar Compra / Factura Exterior *</span>
                    </h3>
                    <p class="text-xs text-indigo-700 dark:text-slate-400 mt-0.5">
                        Elija la compra registrada para importar automáticamente sus productos y el valor FOB base.
                    </p>
                </div>
                <div>
                    <select id="purchaseSelect" name="id_purchase" required class="w-full px-3.5 py-2.5 rounded-xl border border-indigo-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66] outline-none shadow-sm">
                        <option value="">-- Seleccione una compra --</option>
                        @foreach($purchases as $pur)
                            <option value="{{ $pur->id_purchase }}" {{ (string)$selectedPurchaseId === (string)$pur->id_purchase ? 'selected' : '' }}>
                                {{ $pur->purchase_code }} {{ $pur->supplier_invoice_number ? '(Fac: '.$pur->supplier_invoice_number.')' : '' }} - {{ $pur->supplier->name ?? 'Sin Proveedor' }} (${{ number_format($pur->total, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>
            </div>
        @else
            <input type="hidden" name="id_purchase" value="{{ $retaceo->id_purchase }}">
            <div class="bg-slate-100 dark:bg-slate-800 rounded-xl p-4 text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                <span>Factura de Compra Vinculada: <strong class="font-mono text-sm text-[#005e66] dark:text-teal-400">{{ $retaceo->purchase->purchase_code ?? 'CMP-'.$retaceo->id_purchase }}</strong></span>
                <span>Proveedor: <strong>{{ $retaceo->supplier->name ?? 'N/A' }}</strong></span>
            </div>
        @endif

        {{-- Datos de la Liquidación y Póliza Aduanera --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-5">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 border-b border-slate-100 dark:border-slate-700 pb-3 flex items-center gap-2">
                <span>📑 Datos de Importación y Póliza Aduanera</span>
            </h3>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- PROVEEDOR --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Proveedor *</label>
                    <select name="id_supplier" id="id_supplier" required class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="">-- Proveedor --</option>
                        @foreach($suppliers as $sup)
                            <option value="{{ $sup->id_supplier }}" {{ (string)old('id_supplier', $retaceo->id_supplier ?? '') === (string)$sup->id_supplier ? 'selected' : '' }}>
                                {{ $sup->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                {{-- PAÍS ORIGEN --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">País de Origen</label>
                    <input type="text" name="origin_country" id="origin_country" value="{{ old('origin_country', $retaceo->origin_country ?? '') }}" placeholder="Ej: Estados Unidos, China, México" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- FECHA DEL RETACEO --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Liquidación *</label>
                    <input type="datetime-local" name="retaceo_date" id="retaceo_date" required value="{{ old('retaceo_date', isset($retaceo->retaceo_date) ? $retaceo->retaceo_date->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- ESTADO --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Estado *</label>
                    <select name="status" id="status" required class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                        <option value="calculated" {{ old('status', $retaceo->status ?? 'calculated') === 'calculated' ? 'selected' : '' }}>Liquidado / Calculado</option>
                        <option value="draft" {{ old('status', $retaceo->status ?? '') === 'draft' ? 'selected' : '' }}>Borrador</option>
                        <option value="applied" {{ old('status', $retaceo->status ?? '') === 'applied' ? 'selected' : '' }}>Aplicado a Inventario</option>
                    </select>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-5">
                {{-- FACTURA EXTERIOR --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">No. Factura Exterior</label>
                    <input type="text" name="import_invoice_number" id="import_invoice_number" value="{{ old('import_invoice_number', $retaceo->import_invoice_number ?? '') }}" placeholder="Ej: INV-2026-99" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Factura Exterior</label>
                    <input type="date" name="import_invoice_date" id="import_invoice_date" value="{{ old('import_invoice_date', isset($retaceo->import_invoice_date) ? $retaceo->import_invoice_date->format('Y-m-d') : '') }}" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                {{-- PÓLIZA ADUANAL / DECLARACIÓN --}}
                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">No. Póliza / DM Aduana</label>
                    <input type="text" name="import_policy_number" id="import_policy_number" value="{{ old('import_policy_number', $retaceo->import_policy_number ?? '') }}" placeholder="Ej: POL-2026-4482" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Fecha Póliza Aduana</label>
                    <input type="date" name="import_policy_date" id="import_policy_date" value="{{ old('import_policy_date', isset($retaceo->import_policy_date) ? $retaceo->import_policy_date->format('Y-m-d') : '') }}" class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">
                </div>
            </div>

            <!-- FLETES Y GASTOS GLOBALES A PRORRATEAR -->
            <div class="bg-amber-50/70 dark:bg-slate-900/60 p-4 rounded-xl border border-amber-200 dark:border-slate-700 space-y-3">
                <h4 class="font-extrabold text-amber-900 dark:text-amber-300 text-xs uppercase tracking-wider flex items-center gap-2">
                    <span>⚓ Gastos Generales de Importación a Prorratear</span>
                </h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Total Flete Internacional ($) *</label>
                        <input type="number" step="0.0001" min="0" name="total_freight" id="total_freight" value="{{ old('total_freight', (float)($retaceo->total_freight ?? 0)) }}" placeholder="0.00" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-base font-bold font-mono text-slate-800 dark:text-slate-100 focus:border-[#005e66] focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Total Otros Gastos Aduanales / Seguro ($) *</label>
                        <input type="number" step="0.0001" min="0" name="total_expenses" id="total_expenses" value="{{ old('total_expenses', (float)($retaceo->total_expenses ?? 0)) }}" placeholder="0.00" class="w-full rounded-xl border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-base font-bold font-mono text-slate-800 dark:text-slate-100 focus:border-[#005e66] focus:ring-[#005e66]">
                    </div>
                </div>
            </div>

            <div>
                <label class="block text-xs font-bold text-slate-500 uppercase tracking-wider mb-1.5">Notas del Retaceo</label>
                <textarea name="notes" id="notes" rows="2" placeholder="Observaciones de la liquidación o agente aduanal..." class="w-full rounded-xl border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:border-[#005e66] focus:ring-[#005e66]">{{ old('notes', $retaceo->notes ?? '') }}</textarea>
            </div>
        </div>

        {{-- Tabla de Productos y Prorrateo --}}
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-700 pb-3">
                <div>
                    <h3 class="text-base font-extrabold text-slate-800 dark:text-slate-100 flex items-center gap-2">
                        <span>📦 Productos y Distribución de Costos</span>
                    </h3>
                    <p class="text-xs text-slate-400 mt-0.5">El flete y los gastos se distribuyen de forma ponderada según el valor FOB de cada producto.</p>
                </div>
            </div>

            <div class="overflow-x-auto">
                <table class="w-full text-left text-xs" id="retaceoTable">
                    <thead class="bg-slate-50 dark:bg-slate-900/50 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-100 dark:border-slate-700">
                        <tr>
                            <th class="py-3 px-3 w-1/4">Producto</th>
                            <th class="py-3 px-3 text-center w-24">Cantidad</th>
                            <th class="py-3 px-3 w-28">Total FOB ($) *</th>
                            <th class="py-3 px-3 w-24">Flete Asig. ($)</th>
                            <th class="py-3 px-3 w-24">Gastos Asig. ($)</th>
                            <th class="py-3 px-3 w-24">DAI ($)</th>
                            <th class="py-3 px-3 text-right">Costo Total</th>
                            <th class="py-3 px-3 text-right">Costo Unit. Real</th>
                        </tr>
                    </thead>
                    <tbody id="retaceoBody" class="divide-y divide-slate-100 dark:divide-slate-700/60">
                        @if($isEdit && isset($retaceo->details))
                            @foreach($retaceo->details as $index => $detail)
                                <tr class="item-row" data-index="{{ $index }}">
                                    <td class="py-3 px-3">
                                        <input type="hidden" name="details[{{ $index }}][id_purchase_detail]" value="{{ $detail->id_purchase_detail }}">
                                        <input type="hidden" name="details[{{ $index }}][id_product]" value="{{ $detail->id_product }}">
                                        <div class="font-bold text-slate-800 dark:text-slate-200">{{ $detail->product->name ?? 'Producto' }}</div>
                                        <div class="text-[11px] text-slate-400 font-mono">{{ $detail->product->code ?? '' }}</div>
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0.0001" name="details[{{ $index }}][quantity]" value="{{ old('details.'.$index.'.quantity', (float)$detail->quantity) }}" required class="w-full px-2 py-1.5 text-center font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 qty-input">
                                    </td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0" name="details[{{ $index }}][cost_fob]" value="{{ old('details.'.$index.'.cost_fob', (float)$detail->cost_fob) }}" required class="w-full px-2 py-1.5 text-right font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 fob-input">
                                    </td>
                                    <td class="py-3 px-3 font-mono text-right row-freight text-slate-600 dark:text-slate-400">$0.00</td>
                                    <td class="py-3 px-3 font-mono text-right row-expense text-slate-600 dark:text-slate-400">$0.00</td>
                                    <td class="py-3 px-3">
                                        <input type="number" step="0.0001" min="0" name="details[{{ $index }}][dai_amount]" value="{{ old('details.'.$index.'.dai_amount', (float)$detail->dai_amount) }}" class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 dai-input">
                                    </td>
                                    <td class="py-3 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 row-total">$0.00</td>
                                    <td class="py-3 px-3 text-right font-mono font-extrabold text-[#005e66] dark:text-teal-400 text-sm row-unit">$0.00</td>
                                </tr>
                            @endforeach
                        @else
                            <tr id="emptyRow">
                                <td colspan="8" class="py-8 text-center text-slate-400 dark:text-slate-500">
                                    Seleccione una compra en la parte superior para cargar los productos.
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>
            </div>

            <!-- Resumen Total Consolidado -->
            <div class="flex justify-end pt-4 border-t border-slate-100 dark:border-slate-700">
                <div class="w-full md:w-80 space-y-2 text-sm bg-slate-50 dark:bg-slate-900/40 p-4 rounded-xl border border-slate-200 dark:border-slate-700">
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Total FOB:</span>
                        <span class="font-mono font-bold" id="lblFob">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Total Flete:</span>
                        <span class="font-mono font-bold" id="lblFreight">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Total Gastos:</span>
                        <span class="font-mono font-bold" id="lblExpenses">$0.00</span>
                    </div>
                    <div class="flex justify-between text-slate-600 dark:text-slate-400">
                        <span>Total DAI / Aranceles:</span>
                        <span class="font-mono font-bold" id="lblDai">$0.00</span>
                    </div>
                    <div class="flex justify-between text-base font-extrabold text-slate-800 dark:text-slate-100 pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span>Costo Total Liquidado:</span>
                        <span class="font-mono text-[#005e66] dark:text-teal-400 text-xl" id="lblGrandTotal">$0.00</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex items-center justify-end gap-3">
            <a href="{{ route('retaceos.index') }}" class="px-6 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-sm hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                Cancelar
            </a>
            <button type="submit" class="px-8 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white font-bold text-sm shadow-md transition-all transform hover:-translate-y-0.5">
                {{ $isEdit ? 'Actualizar Retaceo' : 'Guardar Liquidación' }}
            </button>
        </div>
    </form>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const purchaseSelect = document.getElementById('purchaseSelect');
        const retaceoBody    = document.getElementById('retaceoBody');
        const freightInput   = document.getElementById('total_freight');
        const expensesInput  = document.getElementById('total_expenses');

        if (purchaseSelect && purchaseSelect.value) {
            cargarCompra(purchaseSelect.value);
        }

        if (purchaseSelect) {
            purchaseSelect.addEventListener('change', function () {
                if (this.value) {
                    cargarCompra(this.value);
                } else {
                    retaceoBody.innerHTML = '<tr id="emptyRow"><td colspan="8" class="py-8 text-center text-slate-400">Seleccione una compra para cargar productos.</td></tr>';
                    calcularProrrateo();
                }
            });
        }

        if (freightInput) freightInput.addEventListener('input', calcularProrrateo);
        if (expensesInput) expensesInput.addEventListener('input', calcularProrrateo);

        function cargarCompra(purchaseId) {
            retaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">Cargando productos de la compra...</td></tr>';

            fetch(`/retaceos/purchase-data/${purchaseId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.id_supplier) document.getElementById('id_supplier').value = data.id_supplier;
                    if (data.origin_country) document.getElementById('origin_country').value = data.origin_country;
                    if (data.supplier_invoice_number) document.getElementById('import_invoice_number').value = data.supplier_invoice_number;
                    if (data.supplier_invoice_date) document.getElementById('import_invoice_date').value = data.supplier_invoice_date;

                    retaceoBody.innerHTML = '';
                    if (!data.details || data.details.length === 0) {
                        retaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">La compra no tiene productos registrados.</td></tr>';
                        return;
                    }

                    data.details.forEach((d, idx) => {
                        const tr = document.createElement('tr');
                        tr.className = 'item-row hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors';
                        tr.dataset.index = idx;

                        tr.innerHTML = `
                            <td class="py-3 px-3">
                                <input type="hidden" name="details[${idx}][id_purchase_detail]" value="${d.id_purchase_detail}">
                                <input type="hidden" name="details[${idx}][id_product]" value="${d.id_product}">
                                <div class="font-bold text-slate-800 dark:text-slate-200">${d.product_name}</div>
                                <div class="text-[11px] text-slate-400 font-mono">${d.unit_name} ${d.product_code ? '• ' + d.product_code : ''}</div>
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0.0001" name="details[${idx}][quantity]" value="${d.quantity}" required class="w-full px-2 py-1.5 text-center font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 qty-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0" name="details[${idx}][cost_fob]" value="${d.cost_fob}" required class="w-full px-2 py-1.5 text-right font-mono font-bold rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 fob-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3 font-mono text-right row-freight text-slate-600 dark:text-slate-400">$0.00</td>
                            <td class="py-3 px-3 font-mono text-right row-expense text-slate-600 dark:text-slate-400">$0.00</td>
                            <td class="py-3 px-3">
                                <input type="number" step="0.0001" min="0" name="details[${idx}][dai_amount]" value="0" class="w-full px-2 py-1.5 text-right font-mono rounded-lg border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 dai-input focus:ring-2 focus:ring-[#005e66]">
                            </td>
                            <td class="py-3 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 row-total">$0.00</td>
                            <td class="py-3 px-3 text-right font-mono font-extrabold text-[#005e66] dark:text-teal-400 text-sm row-unit">$0.00</td>
                        `;
                        retaceoBody.appendChild(tr);
                    });

                    vincularInputs();
                    calcularProrrateo();
                })
                .catch(err => {
                    console.error(err);
                    retaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-rose-500">Error al cargar productos de la compra.</td></tr>';
                });
        }

        function vincularInputs() {
            document.querySelectorAll('.qty-input, .fob-input, .dai-input').forEach(input => {
                input.removeEventListener('input', calcularProrrateo);
                input.addEventListener('input', calcularProrrateo);
            });
        }

        function calcularProrrateo() {
            const totalFreight  = parseFloat(document.getElementById('total_freight')?.value) || 0;
            const totalExpenses = parseFloat(document.getElementById('total_expenses')?.value) || 0;

            const rows = document.querySelectorAll('.item-row');
            let totalFob = 0;
            rows.forEach(r => {
                const fob = parseFloat(r.querySelector('.fob-input')?.value) || 0;
                totalFob += fob;
            });

            let totalDai = 0;
            const count = rows.length;

            rows.forEach(r => {
                const qty  = Math.max(0.0001, parseFloat(r.querySelector('.qty-input')?.value) || 1);
                const fob  = parseFloat(r.querySelector('.fob-input')?.value) || 0;
                const dai  = parseFloat(r.querySelector('.dai-input')?.value) || 0;

                const ratio = (totalFob > 0) ? (fob / totalFob) : (count > 0 ? (1 / count) : 0);

                const freightAmount = totalFreight * ratio;
                const expenseAmount = totalExpenses * ratio;
                const lineTotalCost = fob + freightAmount + expenseAmount + dai;
                const unitCost      = lineTotalCost / qty;

                totalDai += dai;

                const tdFreight = r.querySelector('.row-freight');
                const tdExpense = r.querySelector('.row-expense');
                const tdTotal   = r.querySelector('.row-total');
                const tdUnit    = r.querySelector('.row-unit');

                if (tdFreight) tdFreight.textContent = '$' + freightAmount.toFixed(2);
                if (tdExpense) tdExpense.textContent = '$' + expenseAmount.toFixed(2);
                if (tdTotal) tdTotal.textContent     = '$' + lineTotalCost.toFixed(2);
                if (tdUnit) tdUnit.textContent       = '$' + unitCost.toFixed(2);
            });

            const grandTotal = totalFob + totalFreight + totalExpenses + totalDai;

            document.getElementById('lblFob').textContent        = '$' + totalFob.toFixed(2);
            document.getElementById('lblFreight').textContent    = '$' + totalFreight.toFixed(2);
            document.getElementById('lblExpenses').textContent   = '$' + totalExpenses.toFixed(2);
            document.getElementById('lblDai').textContent        = '$' + totalDai.toFixed(2);
            document.getElementById('lblGrandTotal').textContent = '$' + grandTotal.toFixed(2);
        }

        vincularInputs();
        calcularProrrateo();
    });
</script>
@endsection
