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
            <p class="text-slate-500 dark:text-slate-400 text-sm mt-0.5">Gestione las solicitudes de cotización vinculadas a las solicitudes de compra que envían las sucursales.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="openQuotationRequestModal()" class="w-full md:w-auto bg-customTeal-800 hover:bg-navy-800 text-white font-bold px-5 py-3 rounded-xl shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Solicitud de Cotización</span>
            </button>
        </div>
    </div>

    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
        <div class="bg-white dark:bg-slate-900 p-4 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs flex items-center gap-4">
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
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-4">
        <form method="GET" action="{{ route('purchase-quotation-requests.index') }}" class="flex gap-3 items-center">
            <div class="flex-1 relative">
                <div class="absolute inset-y-0 left-0 pl-3.5 flex items-center pointer-events-none text-slate-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </div>
                <input type="text" name="search" value="{{ $search }}" placeholder="Buscar por código o justificación de solicitud de compra..." class="w-full pl-10 pr-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-800 text-slate-800 dark:text-slate-100 text-sm focus:ring-2 focus:ring-[#005e66] focus:border-transparent transition-all outline-hidden">
            </div>
            <button type="submit" class="bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-semibold py-2.5 px-5 rounded-xl text-sm transition-all shadow-xs flex items-center justify-center gap-1.5">
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

    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl text-sm font-semibold">
            @foreach($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach
        </div>
    @endif

    <!-- Listado Principal -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-4 px-6">ID</th>
                        <th class="py-4 px-6">Solicitudes de Compra</th>
                        <th class="py-4 px-6">Adjudicación</th>
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
                            @php $originRequests = $quotation->purchaseRequests; @endphp
                            <td class="py-4 px-6">
                                <div class="flex flex-wrap items-center gap-1.5">
                                    @forelse($originRequests as $originRequest)
                                        <span class="px-2 py-0.5 rounded-md bg-teal-50 dark:bg-teal-950 text-[#005e66] dark:text-teal-300 text-xs font-mono font-extrabold border border-teal-200 dark:border-teal-800" title="{{ $originRequest->justification }}">
                                            {{ $originRequest->purchase_request_code }}
                                        </span>
                                    @empty
                                        <span class="text-xs text-slate-400">N/A</span>
                                    @endforelse
                                </div>
                                <span class="text-xs text-slate-400 line-clamp-1 mt-1">
                                    {{ $originRequests->map(fn ($pr) => $pr->branch?->name)->filter()->unique()->join(' · ') ?: 'Sin sucursal' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-600 dark:text-slate-300">
                                @php
                                    // Proveedores que ganaron alguno de sus productos
                                    $winners = $quotation->details->map(fn ($d) => $d->quotationDetail?->quotation?->supplier?->name)->filter()->unique();
                                @endphp
                                @if($winners->isNotEmpty())
                                    <span class="text-emerald-600 dark:text-emerald-400">{{ $winners->join(' · ') }}</span>
                                @else
                                    Pendiente
                                @endif
                            </td>
                            <td class="py-4 px-6 whitespace-nowrap">
                                <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotation->created_at->format('d/m/Y') }}</span>
                                <span class="text-xs text-slate-400 block">{{ $quotation->created_at->format('h:i A') }}</span>
                            </td>
                            <td class="py-4 px-6 text-right whitespace-nowrap">
                                <div class="flex items-center justify-end gap-2">
                                    <a href="{{ route('purchase-quotation-requests.show', $quotation->id_purchase_quotation_request) }}" class="p-2.5 rounded-xl bg-sky-50 text-sky-600 hover:bg-sky-100 transition-all flex items-center justify-center inline-flex" title="Ver Detalle">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    </a>
                                </div>
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
                                        <button type="button" onclick="openQuotationRequestModal()" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-customTeal-800 hover:bg-navy-800 text-white font-bold text-sm shadow-md transition-all">
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

{{-- MODAL CREAR SOLICITUD DE COTIZACIÓN --}}
<div id="quotation-request-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-xs p-4">
    <div id="quotation-request-card" class="w-full max-w-2xl bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-slate-100">Nueva Solicitud de Cotización</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Reúna una o varias solicitudes de compra aprobadas; se cotizan completas.</p>
            </div>
            <button type="button" onclick="closeQuotationRequestModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form id="quotation-request-form" method="POST" action="{{ route('purchase-quotation-requests.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Solicitudes de Compra Aprobadas <span class="text-rose-500">*</span>
                        </label>
                        <span id="modal-requests-counter" class="text-xs font-extrabold text-slate-400">0 seleccionadas</span>
                    </div>
                    <div id="modal-requests-list" class="border border-slate-200 dark:border-slate-700 rounded-xl divide-y divide-slate-100 dark:divide-slate-700 max-h-56 overflow-y-auto">
                        <p class="p-4 text-center text-xs text-slate-400">Cargando solicitudes aprobadas...</p>
                    </div>
                    <p id="modal-requests-error" class="hidden text-xs font-semibold text-rose-500">Seleccione al menos una solicitud de compra.</p>
                </div>

                <div class="space-y-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
                            Productos a Cotizar
                        </label>
                        <span id="modal-items-counter" class="text-xs font-extrabold text-slate-400">0 productos</span>
                    </div>
                    <p class="text-xs text-slate-400">Si varias solicitudes piden el mismo producto, se cotiza en una sola línea con la cantidad total.</p>

                    <div id="modal-items-wrapper" class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden min-h-[140px] flex items-center justify-center p-3">
                        <div id="modal-items-placeholder" class="text-center text-slate-400 text-xs space-y-1">
                            <p class="font-semibold text-slate-500 dark:text-slate-400">Seleccione una o varias solicitudes de compra</p>
                            <p>Sus productos se mostrarán aquí.</p>
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
                                    <th class="py-2.5 px-3 text-center">Cantidad Total</th>
                                    <th class="py-2.5 px-3">Por Solicitud</th>
                                </tr>
                            </thead>
                            <tbody id="modal-items-tbody" class="divide-y divide-slate-100 dark:divide-slate-700">
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <button type="button" onclick="closeQuotationRequestModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 hover:bg-navy-800 text-white text-xs font-bold transition-all shadow-xs flex items-center gap-1.5">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Guardar Solicitud</span>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let approvedRequestsCache = [];
const requestDetailsCache = {};
let previewSequence = 0;
const requestDetailsUrl = @json(route('purchase-quotation-requests.request-details', ['id' => '__ID__']));

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

function setRequestsMessage(text) {
    const list = document.getElementById('modal-requests-list');
    list.innerHTML = '';
    const p = document.createElement('p');
    p.className = 'p-4 text-center text-xs text-slate-400';
    p.textContent = text;
    list.appendChild(p);
}

// Lista de solicitudes de compra aprobadas, con una casilla cada una
function loadApprovedRequestsModal() {
    setRequestsMessage('Cargando solicitudes aprobadas...');

    fetch('{{ route('purchase-quotation-requests.approved-requests') }}', {
        headers: { 'Accept': 'application/json' }
    })
    .then(res => res.json())
    .then(data => {
        approvedRequestsCache = data;

        if (data.length === 0) {
            setRequestsMessage('No hay solicitudes de compra aprobadas pendientes de cotizar.');
            refreshQuotationPreview();
            return;
        }

        const list = document.getElementById('modal-requests-list');
        list.innerHTML = '';

        data.forEach(item => {
            const label = document.createElement('label');
            label.className = 'flex items-start gap-3 p-3 cursor-pointer hover:bg-slate-50 dark:hover:bg-slate-900/40 transition-colors';

            const checkbox = document.createElement('input');
            checkbox.type = 'checkbox';
            checkbox.name = 'purchase_requests[]';
            checkbox.value = item.id_purchase_request;
            checkbox.className = 'mt-0.5 rounded-sm text-[#005e66] border-slate-300 w-4 h-4 cursor-pointer';
            checkbox.addEventListener('change', refreshQuotationPreview);

            const text = document.createElement('span');
            text.className = 'flex-1 min-w-0';

            const title = document.createElement('span');
            title.className = 'block text-sm font-bold text-slate-800 dark:text-white';
            title.textContent = item.purchase_request_code + ' · ' + (item.branch ? item.branch.name : 'Sin sucursal');

            const subtitle = document.createElement('span');
            subtitle.className = 'block text-xs text-slate-400 truncate';
            const requiredDate = item.required_date ? new Date(item.required_date).toLocaleDateString() : 'N/A';
            subtitle.textContent = 'Requerida: ' + requiredDate + ' · ' + (item.justification || 'Sin justificación');

            text.append(title, subtitle);
            label.append(checkbox, text);
            list.appendChild(label);
        });

        refreshQuotationPreview();
    })
    .catch(err => {
        console.error('Error al cargar solicitudes:', err);
        setRequestsMessage('Error al cargar las solicitudes aprobadas.');
    });
}

function selectedRequestIds() {
    return Array.from(document.querySelectorAll('#modal-requests-list input[name="purchase_requests[]"]:checked'))
        .map(checkbox => checkbox.value);
}

function formatQuantity(value) {
    return Number(value).toLocaleString(undefined, { maximumFractionDigits: 4 });
}

// Vista previa: una línea por producto y unidad, con la cantidad total y el desglose
async function refreshQuotationPreview() {
    const ids = selectedRequestIds();
    const sequence = ++previewSequence;

    document.getElementById('modal-requests-counter').textContent = ids.length + (ids.length === 1 ? ' seleccionada' : ' seleccionadas');
    if (ids.length > 0) document.getElementById('modal-requests-error').classList.add('hidden');

    const placeholder = document.getElementById('modal-items-placeholder');
    const loading = document.getElementById('modal-items-loading');
    const table = document.getElementById('modal-items-table');
    const tbody = document.getElementById('modal-items-tbody');
    const counter = document.getElementById('modal-items-counter');

    if (ids.length === 0) {
        tbody.innerHTML = '';
        table.classList.add('hidden');
        loading.classList.add('hidden');
        placeholder.classList.remove('hidden');
        counter.textContent = '0 productos';
        return;
    }

    placeholder.classList.add('hidden');
    table.classList.add('hidden');
    loading.classList.remove('hidden');

    try {
        // El detalle de cada solicitud se pide una sola vez
        await Promise.all(ids.filter(id => !requestDetailsCache[id]).map(id =>
            fetch(requestDetailsUrl.replace('__ID__', id), { headers: { 'Accept': 'application/json' } })
                .then(res => res.json())
                .then(details => { requestDetailsCache[id] = details; })
        ));
    } catch (err) {
        console.error('Error al cargar productos:', err);
    }

    // Otra selección empezó mientras se cargaba: esta vista previa ya no aplica
    if (sequence !== previewSequence) return;

    const lines = new Map();
    ids.forEach(id => {
        const request = approvedRequestsCache.find(r => String(r.id_purchase_request) === String(id));
        const code = request ? request.purchase_request_code : '#' + id;
        (requestDetailsCache[id] || []).forEach(detail => {
            const key = detail.id_product + '-' + detail.id_unit;
            if (!lines.has(key)) {
                lines.set(key, { product: detail.product, unit: detail.unit, total: 0, sources: [] });
            }
            const line = lines.get(key);
            const quantity = parseFloat(detail.quantity);
            line.total += quantity;
            line.sources.push(code + ': ' + formatQuantity(quantity));
        });
    });

    tbody.innerHTML = '';
    lines.forEach(line => {
        const row = document.createElement('tr');
        row.className = 'hover:bg-slate-50 dark:hover:bg-slate-900/30';

        const productCell = document.createElement('td');
        productCell.className = 'py-2.5 px-3 font-bold text-slate-800 dark:text-white';
        productCell.textContent = line.product ? line.product.name : 'Producto';

        const unitCell = document.createElement('td');
        unitCell.className = 'py-2.5 px-3';
        unitCell.textContent = line.unit ? (line.unit.abbreviation || line.unit.name) : '—';

        const totalCell = document.createElement('td');
        totalCell.className = 'py-2.5 px-3 text-center font-mono font-extrabold';
        totalCell.textContent = formatQuantity(line.total);

        const sourcesCell = document.createElement('td');
        sourcesCell.className = 'py-2.5 px-3 text-slate-500 dark:text-slate-400';
        sourcesCell.textContent = line.sources.join(' · ');

        row.append(productCell, unitCell, totalCell, sourcesCell);
        tbody.appendChild(row);
    });

    loading.classList.add('hidden');
    table.classList.remove('hidden');
    counter.textContent = lines.size + (lines.size === 1 ? ' producto' : ' productos');
}

document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('quotation-request-form');
    if (form) {
        form.addEventListener('submit', function(e) {
            if (selectedRequestIds().length === 0) {
                e.preventDefault();
                document.getElementById('modal-requests-error').classList.remove('hidden');
            }
        });
    }
});
</script>
@endsection