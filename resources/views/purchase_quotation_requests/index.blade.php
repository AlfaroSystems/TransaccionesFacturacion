@extends('layouts.app')
@section('title', 'Solicitudes de Cotización')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Compras</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400">Cotizaciones</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">Solicitudes de Cotización</h1>
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Gestione las solicitudes de cotización vinculadas a las solicitudes de compra aprobadas.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="openQuotationRequestModal()" class="w-full md:w-auto bg-[#005e66] hover:bg-[#00474f] text-white font-bold px-5 py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Solicitud de Cotización</span>
            </button>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-300 flex items-center justify-center text-xl font-bold">
                📨
            </div>
            <div>
                <span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total Solicitudes</span>
                <span class="text-2xl font-extrabold text-slate-800 dark:text-white">{{ $metrics['total'] }}</span>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-4">
        <form method="GET" action="{{ route('purchase-quotation-requests.index') }}" class="flex gap-3 items-center">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código o justificación de solicitud de compra..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm focus:ring-2 focus:ring-[#005e66] focus:border-transparent transition-all outline-none">
            </div>
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-sm flex items-center justify-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <span>Filtrar</span>
            </button>
            @if($search)
                <a href="{{ route('purchase-quotation-requests.index') }}" class="p-2.5 rounded-xl border border-slate-200 dark:border-slate-700 text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800 transition-all" title="Limpiar filtro">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </a>
            @endif
        </form>
    </div>

    <!-- Listado Principal -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Solicitud de Compra</th>
                        <th class="py-4 px-6">ID Cotización Asociada</th>
                        <th class="py-4 px-6">Fecha Creación</th>
                        <th class="py-4 px-6 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($quotationRequests as $quotation)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-500">
                                #{{ str_pad($quotation->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT) }}
                            </td>
                            <td class="py-4 px-6">
                                <div class="font-extrabold text-slate-800 dark:text-white flex items-center gap-1.5">
                                    <span class="px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-950 text-[#005e66] dark:text-teal-300 text-xs font-mono border border-teal-200 dark:border-teal-800">
                                        {{ $quotation->purchaseRequest->purchase_request_code ?? 'N/A' }}
                                    </span>
                                </div>
                                <span class="text-xs text-slate-400 line-clamp-1 mt-0.5">
                                    {{ $quotation->purchaseRequest->justification ?? 'Sin justificación' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-600 dark:text-slate-300">
                                {{ $quotation->id_purchase_quotation ? '#' . $quotation->id_purchase_quotation : 'Pendiente' }}
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotation->created_at->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-400 block">{{ $quotation->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <a href="{{ route('purchase-quotation-requests.show', $quotation->id_purchase_quotation_request) }}" class="inline-flex items-center gap-1.5 px-3.5 py-1.5 rounded-xl bg-[#005e66]/10 hover:bg-[#005e66] text-[#005e66] hover:text-white font-bold text-xs transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <span>Ver Detalle</span>
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-16 text-center">
                                <div class="max-w-sm mx-auto space-y-3">
                                    <div class="w-16 h-16 mx-auto rounded-full bg-slate-100 dark:bg-slate-800 flex items-center justify-center text-3xl">
                                        📋
                                    </div>
                                    <h3 class="font-extrabold text-slate-700 dark:text-slate-200 text-lg">No hay solicitudes de cotización registradas</h3>
                                    <p class="text-sm text-slate-400">
                                        {{ $search ? 'No se encontraron resultados para los filtros seleccionados.' : 'Comience registrando una solicitud de cotización para una solicitud de compra aprobada.' }}
                                    </p>
                                    <div class="pt-2">
                                        <button type="button" onclick="openQuotationRequestModal()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white font-bold text-sm shadow-md transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span>Nueva Solicitud de Cotización</span>
                                        </button>
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($quotationRequests->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-800">
                {{ $quotationRequests->links() }}
            </div>
        @endif
    </div>
</div>

{{-- ========================================================= --}}
{{-- MODAL CREAR SOLICITUD DE COTIZACIÓN --}}
{{-- ========================================================= --}}
<div id="quotation-request-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm p-4">
    <div id="quotation-request-card" class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200 overflow-hidden max-h-[90vh] flex flex-col">
        {{-- CABECERA DEL MODAL --}}
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-slate-100">Nueva Solicitud de Cotización</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Vincule una solicitud de compra aprobada.</p>
            </div>
            <button type="button" onclick="closeQuotationRequestModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        {{-- FORMULARIO --}}
        <form id="quotation-request-form" method="POST" action="{{ route('purchase-quotation-requests.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                {{-- PASO 1: SELECCIONAR SOLICITUD DE COMPRA --}}
                <div class="space-y-1.5">
                    <label for="modal_id_purchase_request" class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                        Solicitud de Compra Aprobada <span class="text-rose-500">*</span>
                    </label>
                    <select id="modal_id_purchase_request" name="id_purchase_request" required class="w-full px-4 py-3 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900 text-slate-800 dark:text-white text-sm font-medium focus:ring-2 focus:ring-[#005e66] outline-none transition-all">
                        <option value="">-- Cargar solicitudes aprobadas... --</option>
                    </select>
                </div>

                <!-- Preview Solicitud Seleccionada -->
                <div id="modal-request-preview" class="hidden p-3.5 rounded-xl bg-teal-50/60 dark:bg-teal-950/20 border border-teal-100 dark:border-teal-900/50 text-xs space-y-1">
                    <div class="flex justify-between font-bold text-[#005e66] dark:text-teal-300">
                        <span id="modal-prev-code"></span>
                        <span id="modal-prev-date" class="text-slate-600 dark:text-slate-300"></span>
                    </div>
                    <p id="modal-prev-justification" class="text-slate-600 dark:text-slate-400 italic"></p>
                </div>

                {{-- PASO 2: TABLA DE ÍTEMS Y CANTIDADES --}}
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Ítems y Cantidades a Cotizar
                        </label>
                        <span id="modal-items-counter" class="text-xs font-extrabold text-slate-400">0 ítems</span>
                    </div>

                    <div id="modal-items-wrapper" class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden min-h-[140px] flex items-center justify-center p-3">
                        <div id="modal-items-placeholder" class="text-center text-slate-400 text-xs space-y-1">
                            <p class="font-semibold text-slate-500 dark:text-slate-400">Seleccione una solicitud de compra aprobada</p>
                            <p>Los detalles se cargarán automáticamente.</p>
                        </div>
                        <div id="modal-items-loading" class="hidden text-center text-slate-400 text-xs space-y-2">
                            <div class="inline-block animate-spin rounded-full h-6 w-6 border-2 border-[#005e66] border-t-transparent"></div>
                            <p>Cargando productos...</p>
                        </div>
                        <table id="modal-items-table" class="hidden w-full text-left text-xs text-slate-600 dark:text-slate-300">
                            <thead class="bg-slate-50 dark:bg-slate-900/80 font-extrabold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-3">Unidad</th>
                                    <th class="py-2.5 px-3 text-center">Cant. Solicitada</th>
                                    <th class="py-2.5 px-3 text-center w-32">Cant. a Cotizar</th>
                                </tr>
                            </thead>
                            <tbody id="modal-items-tbody" class="divide-y divide-slate-100 dark:divide-slate-700">
                                <!-- Dinámico -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            {{-- BOTONES DEL MODAL --}}
            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <button type="button" onclick="closeQuotationRequestModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-xs font-bold transition-all shadow-sm flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Guardar Solicitud</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let approvedRequestsCache = [];

function openQuotationRequestModal() {
    const modal = document.getElementById('quotation-request-modal');
    const card = document.getElementById('quotation-request-card');

    modal.classList.remove('hidden');
    modal.classList.add('flex');

    setTimeout(() => {
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 10);

    loadApprovedRequestsModal();
}

function closeQuotationRequestModal() {
    const modal = document.getElementById('quotation-request-modal');
    const card = document.getElementById('quotation-request-card');

    card.classList.remove('scale-100');
    card.classList.add('scale-95');

    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

function loadApprovedRequestsModal(selectedId = null) {
    const select = document.getElementById('modal_id_purchase_request');
    select.disabled = true;

    fetch('{{ route('purchase-quotation-requests.approved-requests') }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        approvedRequestsCache = data;
        select.innerHTML = '';

        if (data.length === 0) {
            select.innerHTML = '<option value="">No hay solicitudes de compra en estado approved</option>';
            clearModalItemsTable();
            return;
        }

        const defaultOption = document.createElement('option');
        defaultOption.value = '';
        defaultOption.textContent = '-- Seleccione una Solicitud de Compra --';
        select.appendChild(defaultOption);

        data.forEach(item => {
            const opt = document.createElement('option');
            opt.value = item.id_purchase_request;
            const formattedDate = item.required_date ? new Date(item.required_date).toLocaleDateString() : 'N/A';
            opt.textContent = `${item.purchase_request_code} - Requerida: ${formattedDate} (${item.justification ? item.justification.substring(0, 35) + '...' : 'Sin justificación'})`;
            if (selectedId && selectedId == item.id_purchase_request) {
                opt.selected = true;
            }
            select.appendChild(opt);
        });

        if (selectedId) {
            handleModalRequestChange(selectedId);
        }
    })
    .catch(err => {
        console.error('Error al cargar solicitudes:', err);
        select.innerHTML = '<option value="">Error al cargar solicitudes aprobadas</option>';
    })
    .finally(() => {
        select.disabled = false;
    });
}

function handleModalRequestChange(id) {
    const preview = document.getElementById('modal-request-preview');
    const prevCode = document.getElementById('modal-prev-code');
    const prevDate = document.getElementById('modal-prev-date');
    const prevJustification = document.getElementById('modal-prev-justification');

    if (!id) {
        preview.classList.add('hidden');
        clearModalItemsTable();
        return;
    }

    const found = approvedRequestsCache.find(r => r.id_purchase_request == id);
    if (found) {
        prevCode.textContent = found.purchase_request_code;
        prevDate.textContent = 'Requerida: ' + (found.required_date ? new Date(found.required_date).toLocaleDateString() : 'N/A');
        prevJustification.textContent = found.justification || 'Sin justificación';
        preview.classList.remove('hidden');
    }

    loadModalRequestItems(id);
}

function loadModalRequestItems(purchaseRequestId) {
    const placeholder = document.getElementById('modal-items-placeholder');
    const loading = document.getElementById('modal-items-loading');
    const table = document.getElementById('modal-items-table');
    const tbody = document.getElementById('modal-items-tbody');
    const counter = document.getElementById('modal-items-counter');

    placeholder.classList.add('hidden');
    table.classList.add('hidden');
    loading.classList.remove('hidden');
    tbody.innerHTML = '';

    const url = '{{ url('purchase-quotation-requests/request-details') }}/' + purchaseRequestId;

    fetch(url, {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => {
        if (!res.ok) throw new Error('Error al consultar el servidor');
        return res.json();
    })
    .then(details => {
        loading.classList.add('hidden');

        if (!Array.isArray(details) || details.length === 0) {
            placeholder.classList.remove('hidden');
            placeholder.innerHTML = '<p class="text-rose-500 font-bold">Esta solicitud no tiene productos registrados.</p>';
            counter.textContent = '0 ítems';
            return;
        }

        counter.textContent = `${details.length} ítem(s)`;
        table.classList.remove('hidden');

        details.forEach((detail, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'hover:bg-slate-50/60 dark:hover:bg-slate-700/40 transition-colors';

            const productName = detail.product ? detail.product.name : 'Producto no disponible';
            const unitName = detail.unit ? detail.unit.name : 'Unidad';
            const originalQty = parseFloat(detail.quantity) || 1;

            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <span class="font-bold text-slate-800 dark:text-white">${productName}</span>
                    <input type="hidden" name="items[${idx}][id_purchase_request_detail]" value="${detail.id_purchase_request_detail}">
                </td>
                <td class="py-2.5 px-3">
                    <span class="px-2 py-0.5 rounded bg-slate-100 dark:bg-slate-700 text-slate-600 dark:text-slate-300 font-semibold">${unitName}</span>
                </td>
                <td class="py-2.5 px-3 text-center font-bold font-mono">${originalQty}</td>
                <td class="py-2.5 px-3 text-center">
                    <input type="number" step="0.0001" min="0.0001" name="items[${idx}][quantity]" value="${originalQty}" required class="w-24 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                </td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(err => {
        console.error('Error al cargar ítems:', err);
        loading.classList.add('hidden');
        placeholder.classList.remove('hidden');
        placeholder.innerHTML = '<p class="text-rose-500 font-bold">Error al cargar productos.</p>';
        counter.textContent = '0 ítems';
    });
}

function clearModalItemsTable() {
    document.getElementById('modal-items-placeholder').classList.remove('hidden');
    document.getElementById('modal-items-table').classList.add('hidden');
    document.getElementById('modal-items-loading').classList.add('hidden');
    document.getElementById('modal-items-tbody').innerHTML = '';
    document.getElementById('modal-items-counter').textContent = '0 ítems';
}

document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('modal_id_purchase_request');
    if (select) {
        select.addEventListener('change', function() {
            handleModalRequestChange(this.value);
        });
    }

    const form = document.getElementById('quotation-request-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            const itemsRows = document.getElementById('modal-items-tbody').querySelectorAll('tr').length;
            if (itemsRows === 0) {
                e.preventDefault();
                alert('Debe seleccionar una solicitud de compra que contenga al menos un producto.');
                return false;
            }
        });
    }
});
</script>
@endsection
