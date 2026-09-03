@extends('layouts.app')
@section('title', 'Nueva Solicitud de Cotización a Proveedores')

@section('content')
<div class="w-full max-w-6xl mx-auto space-y-6 animate-fade-in duration-300">
    <!-- Encabezado y Navegación -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-quotation-requests.index') }}" class="text-xs font-bold text-slate-400 hover:text-[#005e66] transition-colors">Solicitudes de Cotización</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-xs font-extrabold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Nueva Invitación</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Convocatoria a Cotizar</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Vincule una solicitud de compra aprobada y seleccione los proveedores invitados a cotizar.</p>
        </div>
        <div>
            <a href="{{ route('purchase-quotation-requests.index') }}" class="px-5 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm transition-all flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Errores de Validación -->
    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-5 py-4 rounded-2xl shadow-sm">
            <p class="font-extrabold text-sm mb-2 flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/></svg>
                <span>Verifique los siguientes campos:</span>
            </p>
            <ul class="list-disc pl-6 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form id="quotation-form" action="{{ route('purchase-quotation-requests.store') }}" method="POST" class="space-y-6">
        @csrf

        <!-- SECCIÓN 1: Selector AJAX de Solicitudes de Compra en estado APPROVED -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 font-extrabold flex items-center justify-center text-sm">1</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitud de Compra Aprobada</h2>
                        <p class="text-xs text-slate-400">Seleccione la solicitud de origen para extraer los ítems y cantidades requeridas.</p>
                    </div>
                </div>
                <button type="button" id="btn-refresh-requests" class="text-xs text-[#005e66] dark:text-teal-400 hover:underline flex items-center gap-1 font-bold">
                    <svg class="w-4 h-4" id="icon-refresh" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                    <span>Actualizar vía AJAX</span>
                </button>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-12 gap-4">
                <div class="md:col-span-8">
                    <label for="id_purchase_request" class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1.5">
                        Seleccionar Solicitud <span class="text-rose-500">*</span>
                    </label>
                    <select id="id_purchase_request" name="id_purchase_request" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white text-sm font-medium focus:ring-2 focus:ring-[#005e66] focus:border-transparent outline-none transition-all">
                        <option value="">-- Buscando solicitudes aprobadas... --</option>
                    </select>
                </div>
                <div class="md:col-span-4 flex items-end">
                    <div id="request-status-badge" class="w-full px-4 py-3 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-dashed border-slate-200 dark:border-slate-700 text-xs text-slate-400 flex items-center justify-center gap-2">
                        <span>Estado: Ninguna seleccionada</span>
                    </div>
                </div>
            </div>

            <!-- Card de Información de la Solicitud Seleccionada -->
            <div id="request-preview-card" class="hidden p-4 rounded-xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/50 space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold">Código:</span>
                        <span id="prev-code" class="font-bold text-[#005e66] dark:text-teal-300 font-mono text-sm"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold">Fecha Requerida:</span>
                        <span id="prev-required-date" class="font-bold text-slate-700 dark:text-slate-200"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold">Estado de Origen:</span>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-extrabold bg-emerald-100 text-emerald-800 dark:bg-emerald-900/60 dark:text-emerald-300">
                            Aprobada (approved)
                        </span>
                    </div>
                </div>
                <div class="text-xs pt-1 border-t border-teal-100 dark:border-teal-900/40">
                    <span class="text-slate-400 font-semibold">Justificación: </span>
                    <span id="prev-justification" class="text-slate-700 dark:text-slate-300 italic"></span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Checkbox list de Proveedores Convocados -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex flex-col sm:flex-row justify-between items-start sm:items-center gap-2 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 font-extrabold flex items-center justify-center text-sm">2</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Proveedores Convocados</h2>
                        <p class="text-xs text-slate-400">Seleccione uno o más proveedores a los que se enviará la solicitud formal.</p>
                    </div>
                </div>
                <div class="flex items-center gap-2 self-end sm:self-auto">
                    <button type="button" id="btn-select-all-suppliers" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Seleccionar Todos
                    </button>
                    <button type="button" id="btn-deselect-all-suppliers" class="px-3 py-1.5 rounded-lg text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 hover:bg-slate-200 dark:hover:bg-slate-700 transition-colors">
                        Deseleccionar
                    </button>
                    <span id="selected-suppliers-counter" class="ml-2 px-2.5 py-1 rounded-full text-xs font-extrabold bg-[#005e66] text-white">
                        0 seleccionados
                    </span>
                </div>
            </div>

            <!-- Buscador rápido en vivo de proveedores -->
            <div class="relative">
                <input type="text" id="filter-suppliers-input" placeholder="Filtrar proveedor por nombre, código o email..." class="w-full pl-10 pr-4 py-2 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white text-xs outline-none focus:ring-2 focus:ring-[#005e66]">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
            </div>

            <!-- Grid de Checkboxes de Proveedores -->
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3 max-h-72 overflow-y-auto pr-1" id="suppliers-container">
                @forelse($suppliers as $supplier)
                    <label class="supplier-item group flex items-start gap-3 p-3.5 rounded-xl border border-slate-200 dark:border-slate-700 hover:border-[#005e66] dark:hover:border-teal-400 bg-slate-50/40 dark:bg-slate-800/50 cursor-pointer transition-all hover:shadow-sm" data-search="{{ strtolower($supplier->name . ' ' . $supplier->code . ' ' . $supplier->email) }}">
                        <input type="checkbox" name="supplier_ids[]" value="{{ $supplier->id_supplier }}" class="supplier-checkbox mt-1 w-4 h-4 text-[#005e66] rounded border-slate-300 focus:ring-[#005e66] dark:border-slate-600 dark:bg-slate-700" {{ in_array($supplier->id_supplier, old('supplier_ids', [])) ? 'checked' : '' }}>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center justify-between">
                                <span class="font-bold text-sm text-slate-800 dark:text-white truncate block">{{ $supplier->name }}</span>
                                @if($supplier->country)
                                    <span class="text-[10px] uppercase font-bold text-slate-400 bg-slate-200/60 dark:bg-slate-700 px-1.5 py-0.5 rounded">{{ $supplier->country }}</span>
                                @endif
                            </div>
                            <span class="text-xs text-slate-400 block truncate mt-0.5">{{ $supplier->email }}</span>
                            @if($supplier->phone)
                                <span class="text-[11px] text-slate-400 block mt-0.5">📞 {{ $supplier->phone }}</span>
                            @endif
                        </div>
                    </label>
                @empty
                    <div class="col-span-3 text-center py-6 text-slate-400 text-sm">
                        No hay proveedores activos registrados en el sistema.
                    </div>
                @endforelse
            </div>
        </div>

        <!-- SECCIÓN 3: Tabla de Ítems Requeridos (Cargada vía AJAX) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 font-extrabold flex items-center justify-center text-sm">3</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Ítems a Cotizar</h2>
                        <p class="text-xs text-slate-400">Productos extraídos directamente de la solicitud de compra aprobada.</p>
                    </div>
                </div>
                <div id="items-counter" class="text-xs font-extrabold text-slate-400">
                    0 ítems cargados
                </div>
            </div>

            <!-- Contenedor dinámico de la tabla AJAX -->
            <div id="items-table-wrapper" class="overflow-x-auto">
                <div id="items-placeholder" class="py-12 text-center text-slate-400 text-sm space-y-2">
                    <div class="text-3xl">📋</div>
                    <p class="font-semibold text-slate-600 dark:text-slate-300">Seleccione una Solicitud de Compra aprobada en el paso 1</p>
                    <p class="text-xs text-slate-400">Los productos requeridos se cargarán automáticamente aquí mediante AJAX.</p>
                </div>

                <div id="items-loading" class="hidden py-12 text-center text-slate-400 space-y-3">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#005e66] border-t-transparent"></div>
                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Cargando productos de la solicitud...</p>
                </div>

                <table id="items-table" class="hidden w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Unidad</th>
                            <th class="py-3 px-4 text-center">Cant. Solicitada</th>
                            <th class="py-3 px-4 text-center w-40">Cant. a Cotizar <span class="text-rose-500">*</span></th>
                            <th class="py-3 px-4">Notas para el Proveedor</th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <!-- Generado dinámicamente con JS -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECCIÓN 4: Notas / Instrucciones Generales -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-3">
            <label for="notes" class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                Instrucciones o Condiciones Comerciales Adicionales (Opcional)
            </label>
            <textarea id="notes" name="notes" rows="3" placeholder="Indique términos de entrega esperados, garantía requerida, fecha límite para recibir la cotización..." class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-[#005e66] focus:border-transparent outline-none transition-all">{{ old('notes') }}</textarea>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
            <a href="{{ route('purchase-quotation-requests.index') }}" class="w-full sm:w-auto px-6 py-3 rounded-full border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm transition-all text-center">
                Cancelar
            </a>
            <button type="submit" id="btn-submit" class="w-full sm:w-auto bg-[#005e66] hover:bg-[#3cb0a4] text-white font-extrabold px-8 py-3 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                <span>Generar Convocatoria a Proveedores</span>
            </button>
        </div>
    </form>
</div>

<!-- Lógica Frontend AJAX y Reactividad -->
<script>
document.addEventListener('DOMContentLoaded', function() {
    const selectPurchaseRequest = document.getElementById('id_purchase_request');
    const btnRefresh = document.getElementById('btn-refresh-requests');
    const iconRefresh = document.getElementById('icon-refresh');
    const previewCard = document.getElementById('request-preview-card');
    const prevCode = document.getElementById('prev-code');
    const prevRequiredDate = document.getElementById('prev-required-date');
    const prevJustification = document.getElementById('prev-justification');
    const statusBadge = document.getElementById('request-status-badge');

    const itemsPlaceholder = document.getElementById('items-placeholder');
    const itemsLoading = document.getElementById('items-loading');
    const itemsTable = document.getElementById('items-table');
    const itemsTbody = document.getElementById('items-tbody');
    const itemsCounter = document.getElementById('items-counter');

    const filterSuppliersInput = document.getElementById('filter-suppliers-input');
    const supplierItems = document.querySelectorAll('.supplier-item');
    const supplierCheckboxes = document.querySelectorAll('.supplier-checkbox');
    const btnSelectAll = document.getElementById('btn-select-all-suppliers');
    const btnDeselectAll = document.getElementById('btn-deselect-all-suppliers');
    const counterBadge = document.getElementById('selected-suppliers-counter');

    let approvedRequestsCache = [];

    // 1. Cargar Solicitudes Aprobadas vía AJAX
    function loadApprovedRequests(selectedId = null) {
        iconRefresh.classList.add('animate-spin');
        selectPurchaseRequest.disabled = true;

        fetch('{{ route('purchase-quotation-requests.approved-requests') }}', {
            headers: { 'Accept': 'application/json' }
        })
        .then(response => response.json())
        .then(data => {
            approvedRequestsCache = data;
            selectPurchaseRequest.innerHTML = '';

            if (data.length === 0) {
                selectPurchaseRequest.innerHTML = '<option value="">No hay solicitudes de compra en estado approved</option>';
                statusBadge.innerHTML = '<span class="text-rose-500 font-bold">Sin solicitudes aprobadas disponibles</span>';
                previewCard.classList.add('hidden');
                clearItemsTable();
                return;
            }

            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = '-- Seleccione una Solicitud de Compra --';
            selectPurchaseRequest.appendChild(defaultOption);

            data.forEach(item => {
                const opt = document.createElement('option');
                opt.value = item.id_purchase_request;
                const formattedDate = item.required_date ? new Date(item.required_date).toLocaleDateString() : 'N/A';
                opt.textContent = `${item.purchase_request_code} - Requerida: ${formattedDate} (${item.justification ? item.justification.substring(0, 45) + '...' : 'Sin justificación'})`;
                if (selectedId && selectedId == item.id_purchase_request) {
                    opt.selected = true;
                }
                selectPurchaseRequest.appendChild(opt);
            });

            if (selectedId) {
                handleRequestChange(selectedId);
            }
        })
        .catch(err => {
            console.error('Error al cargar solicitudes:', err);
            selectPurchaseRequest.innerHTML = '<option value="">Error al cargar solicitudes aprobadas</option>';
        })
        .finally(() => {
            iconRefresh.classList.remove('animate-spin');
            selectPurchaseRequest.disabled = false;
        });
    }

    // 2. Manejador de cambio de Solicitud de Compra
    function handleRequestChange(id) {
        if (!id) {
            previewCard.classList.add('hidden');
            statusBadge.innerHTML = '<span>Estado: Ninguna seleccionada</span>';
            clearItemsTable();
            return;
        }

        const found = approvedRequestsCache.find(r => r.id_purchase_request == id);
        if (found) {
            prevCode.textContent = found.purchase_request_code;
            prevRequiredDate.textContent = found.required_date ? new Date(found.required_date).toLocaleDateString() : 'N/A';
            prevJustification.textContent = found.justification || 'Sin justificación especificada';
            previewCard.classList.remove('hidden');
            statusBadge.innerHTML = '<span class="text-emerald-600 font-extrabold">✓ Solicitud Aprobada Seleccionada</span>';
        }

        loadRequestItems(id);
    }

    // 3. Cargar Ítems vía AJAX
    function loadRequestItems(purchaseRequestId) {
        itemsPlaceholder.classList.add('hidden');
        itemsTable.classList.add('hidden');
        itemsLoading.classList.remove('hidden');
        itemsTbody.innerHTML = '';

        const url = '{{ url('purchase-quotation-requests/request-details') }}/' + purchaseRequestId;

        fetch(url, {
            headers: { 'Accept': 'application/json' }
        })
        .then(res => res.json())
        .then(details => {
            itemsLoading.classList.add('hidden');

            if (!details || details.length === 0) {
                itemsPlaceholder.classList.remove('hidden');
                itemsPlaceholder.innerHTML = `
                    <div class="text-3xl text-amber-500">⚠️</div>
                    <p class="font-semibold text-slate-700 dark:text-slate-200">Esta solicitud de compra no tiene ítems registrados.</p>
                `;
                itemsCounter.textContent = '0 ítems cargados';
                return;
            }

            itemsCounter.textContent = `${details.length} ítem(s) cargado(s)`;
            itemsTable.classList.remove('hidden');

            details.forEach((detail, idx) => {
                const tr = document.createElement('tr');
                tr.className = 'hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors';

                const productName = detail.product ? detail.product.name : 'Producto no disponible';
                const productSku = detail.product ? (detail.product.sku || detail.product.barcode || '') : '';
                const unitName = detail.unit ? detail.unit.name : 'Unidad';
                const originalQty = parseFloat(detail.quantity) || 1;

                tr.innerHTML = `
                    <td class="py-3 px-4 font-mono text-xs text-slate-400">${idx + 1}</td>
                    <td class="py-3 px-4">
                        <div class="font-bold text-slate-800 dark:text-white">${productName}</div>
                        ${productSku ? `<span class="text-[11px] font-mono text-slate-400">SKU: ${productSku}</span>` : ''}
                        <input type="hidden" name="items[${idx}][id_purchase_request_detail]" value="${detail.id_purchase_request_detail}">
                    </td>
                    <td class="py-3 px-4">
                        <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-semibold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300">
                            ${unitName}
                        </span>
                    </td>
                    <td class="py-3 px-4 text-center font-bold text-slate-700 dark:text-slate-200 font-mono">
                        ${originalQty}
                    </td>
                    <td class="py-3 px-4 text-center">
                        <input type="number" step="0.0001" min="0.0001" name="items[${idx}][quantity]" value="${originalQty}" required class="w-32 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-center font-bold text-slate-800 dark:text-white text-sm focus:ring-2 focus:ring-[#005e66] outline-none">
                    </td>
                    <td class="py-3 px-4">
                        <input type="text" name="items[${idx}][notes]" placeholder="Nota opcional para el ítem..." class="w-full px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 text-xs focus:ring-1 focus:ring-[#005e66] outline-none">
                    </td>
                `;
                itemsTbody.appendChild(tr);
            });
        })
        .catch(err => {
            console.error('Error al cargar ítems:', err);
            itemsLoading.classList.add('hidden');
            itemsPlaceholder.classList.remove('hidden');
            itemsPlaceholder.innerHTML = '<p class="text-rose-500 font-bold">Error al cargar los productos de la solicitud.</p>';
        });
    }

    function clearItemsTable() {
        itemsPlaceholder.classList.remove('hidden');
        itemsTable.classList.add('hidden');
        itemsLoading.classList.add('hidden');
        itemsTbody.innerHTML = '';
        itemsCounter.textContent = '0 ítems cargados';
    }

    // 4. Actualizar contador de proveedores seleccionados
    function updateSuppliersCounter() {
        const count = document.querySelectorAll('.supplier-checkbox:checked').length;
        counterBadge.textContent = `${count} seleccionado${count === 1 ? '' : 's'}`;
        if (count > 0) {
            counterBadge.className = 'ml-2 px-2.5 py-1 rounded-full text-xs font-extrabold bg-[#005e66] text-white';
        } else {
            counterBadge.className = 'ml-2 px-2.5 py-1 rounded-full text-xs font-extrabold bg-slate-300 dark:bg-slate-700 text-slate-600 dark:text-slate-300';
        }
    }

    // 5. Filtro en vivo de proveedores
    filterSuppliersInput.addEventListener('input', function() {
        const term = this.value.toLowerCase().trim();
        supplierItems.forEach(item => {
            const data = item.getAttribute('data-search') || '';
            if (data.includes(term)) {
                item.classList.remove('hidden');
            } else {
                item.classList.add('hidden');
            }
        });
    });

    // 6. Botones Seleccionar / Deseleccionar todos los proveedores
    btnSelectAll.addEventListener('click', function() {
        supplierItems.forEach(item => {
            if (!item.classList.contains('hidden')) {
                const chk = item.querySelector('.supplier-checkbox');
                if (chk) chk.checked = true;
            }
        });
        updateSuppliersCounter();
    });

    btnDeselectAll.addEventListener('click', function() {
        supplierCheckboxes.forEach(chk => chk.checked = false);
        updateSuppliersCounter();
    });

    supplierCheckboxes.forEach(chk => {
        chk.addEventListener('change', updateSuppliersCounter);
    });

    selectPurchaseRequest.addEventListener('change', function() {
        handleRequestChange(this.value);
    });

    btnRefresh.addEventListener('click', function() {
        loadApprovedRequests(selectPurchaseRequest.value);
    });

    // Validación al enviar el formulario
    document.getElementById('quotation-form').addEventListener('submit', function(e) {
        const checkedSuppliers = document.querySelectorAll('.supplier-checkbox:checked').length;
        if (checkedSuppliers === 0) {
            e.preventDefault();
            alert('Por favor seleccione al menos un proveedor para convocar.');
            return false;
        }

        const itemsRows = itemsTbody.querySelectorAll('tr').length;
        if (itemsRows === 0) {
            e.preventDefault();
            alert('Debe seleccionar una solicitud de compra que contenga al menos un producto.');
            return false;
        }
    });

    // Carga inicial
    const oldRequestId = '{{ old('id_purchase_request') }}';
    loadApprovedRequests(oldRequestId || null);
    updateSuppliersCounter();
});
</script>
@endsection
