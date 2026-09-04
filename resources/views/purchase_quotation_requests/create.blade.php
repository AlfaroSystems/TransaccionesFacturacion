@extends('layouts.app')
@section('title', 'Nueva Solicitud de Cotización')

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-6 animate-fade-in duration-300">
    <!-- Encabezado y Navegación -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-quotation-requests.index') }}" class="text-xs font-bold text-slate-400 hover:text-[#005e66] transition-colors">Solicitudes de Cotización</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-xs font-extrabold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Nueva Solicitud</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Registrar Solicitud de Cotización</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Seleccione una solicitud de compra aprobada para registrar su correspondiente solicitud de cotización.</p>
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

        <!-- SECCIÓN 1: Selección de Solicitud de Compra -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 font-extrabold flex items-center justify-center text-sm">1</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitud de Compra Aprobada</h2>
                        <p class="text-xs text-slate-400">Seleccione la solicitud de origen para vincular sus detalles y cantidades.</p>
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

            <!-- Card Preview Solicitud Seleccionada -->
            <div id="request-preview-card" class="hidden p-4 rounded-xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/50 space-y-2">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                    <div>
                        <span class="text-slate-400 block font-semibold">Código:</span>
                        <span id="prev-code" class="font-bold text-[#005e66] dark:text-teal-300 font-mono text-sm"></span>
                    </div>
                    <div>
                        <span class="text-slate-400 block font-semibold">Fecha Requerida:</span>
                        <span id="prev-required-date" class="font-bold text-slate-700 dark:text-slate-200"></span>
                    </div>
                </div>
                <div class="text-xs pt-1 border-t border-teal-100 dark:border-teal-900/40">
                    <span class="text-slate-400 font-semibold">Justificación: </span>
                    <span id="prev-justification" class="text-slate-700 dark:text-slate-300 italic"></span>
                </div>
            </div>
        </div>

        <!-- SECCIÓN 2: Ítems y Cantidades a Cotizar -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="flex items-center gap-2.5">
                    <span class="w-8 h-8 rounded-lg bg-teal-100 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 font-extrabold flex items-center justify-center text-sm">2</span>
                    <div>
                        <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Detalles e Ítems a Cotizar</h2>
                        <p class="text-xs text-slate-400">Productos requeridos y cantidad especificada.</p>
                    </div>
                </div>
                <div id="items-counter" class="text-xs font-extrabold text-slate-400">
                    0 ítems cargados
                </div>
            </div>

            <!-- Contenedor dinámico de la tabla -->
            <div id="items-table-wrapper" class="overflow-x-auto">
                <div id="items-placeholder" class="py-12 text-center text-slate-400 text-sm space-y-2">
                    <div class="text-3xl">📋</div>
                    <p class="font-semibold text-slate-600 dark:text-slate-300">Seleccione una Solicitud de Compra aprobada en el paso 1</p>
                    <p class="text-xs text-slate-400">Los detalles de productos se cargarán automáticamente.</p>
                </div>

                <div id="items-loading" class="hidden py-12 text-center text-slate-400 space-y-3">
                    <div class="inline-block animate-spin rounded-full h-8 w-8 border-4 border-[#005e66] border-t-transparent"></div>
                    <p class="text-sm font-semibold text-slate-600 dark:text-slate-300">Cargando productos...</p>
                </div>

                <table id="items-table" class="hidden w-full text-left text-sm text-slate-600 dark:text-slate-300">
                    <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4">Unidad</th>
                            <th class="py-3 px-4 text-center">Cant. Solicitada</th>
                            <th class="py-3 px-4 text-center w-40">Cant. a Cotizar <span class="text-rose-500">*</span></th>
                        </tr>
                    </thead>
                    <tbody id="items-tbody" class="divide-y divide-slate-100 dark:divide-slate-800">
                        <!-- Generado dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-center justify-end gap-3 pt-2">
            <a href="{{ route('purchase-quotation-requests.index') }}" class="w-full sm:w-auto px-6 py-3 rounded-full border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-sm transition-all text-center">
                Cancelar
            </a>
            <button type="submit" id="btn-submit" class="w-full sm:w-auto bg-[#005e66] hover:bg-[#3cb0a4] text-white font-extrabold px-8 py-3 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>Guardar Solicitud de Cotización</span>
            </button>
        </div>
    </form>
</div>

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

    let approvedRequestsCache = [];

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

    selectPurchaseRequest.addEventListener('change', function() {
        handleRequestChange(this.value);
    });

    btnRefresh.addEventListener('click', function() {
        loadApprovedRequests(selectPurchaseRequest.value);
    });

    document.getElementById('quotation-form').addEventListener('submit', function(e) {
        const itemsRows = itemsTbody.querySelectorAll('tr').length;
        if (itemsRows === 0) {
            e.preventDefault();
            alert('Debe seleccionar una solicitud de compra que contenga al menos un producto.');
            return false;
        }
    });

    const oldRequestId = '{{ old('id_purchase_request') }}';
    loadApprovedRequests(oldRequestId || null);
});
</script>
@endsection
