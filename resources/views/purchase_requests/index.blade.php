@extends('layouts.app')
@section('title', 'Solicitudes de Compra')
@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg">Compras</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">Módulo de Solicitudes</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">Solicitudes de Compra</h1>
            <p class="text-slate-500 text-sm mt-0.5">Registre y gestione las solicitudes de productos requeridos por sucursal y bodega.</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="openModal('create-purchase-request-modal')" class="w-full md:w-auto bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold px-5 py-3 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Solicitud</span>
            </button>
        </div>
    </div>
    <!-- Mensajes -->
    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-5 py-4 rounded-2xl font-semibold text-sm">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl font-semibold text-sm">{{ session('error') }}</div>
    @endif
    @if($errors->any())
        <div class="bg-rose-50 border border-rose-200 text-rose-700 px-5 py-4 rounded-2xl">
            <p class="font-extrabold text-sm mb-2">No se pudo guardar la solicitud:</p>
            <ul class="list-disc pl-5 text-sm space-y-1">
                @foreach($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif
    <!-- Métricas -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-blue-50 text-[#005e66] flex items-center justify-center text-xl font-bold">📋</div>
            <div><span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Total</span><span class="text-xl font-extrabold text-slate-800">{{ $purchaseRequests->total() }}</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-slate-100 text-slate-600 flex items-center justify-center text-xl font-bold">✎</div>
            <div><span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Borradores</span><span class="text-xl font-extrabold text-slate-800">{{ $purchaseRequests->getCollection()->where('status', 'draft')->count() }}</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center text-xl font-bold">⏳</div>
            <div><span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Pendientes</span><span class="text-xl font-extrabold text-slate-800">{{ $purchaseRequests->getCollection()->where('status', 'pending')->count() }}</span></div>
        </div>
        <div class="bg-white p-4 rounded-2xl border border-slate-100 shadow-sm flex items-center gap-4">
            <div class="w-12 h-12 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center text-xl font-bold">✓</div>
            <div><span class="text-xs font-bold text-slate-400 uppercase tracking-wider block">Aprobadas</span><span class="text-xl font-extrabold text-slate-800">{{ $purchaseRequests->getCollection()->where('status', 'approved')->count() }}</span></div>
        </div>
    </div>
    <!-- Filtros -->
    <div class="bg-white rounded-2xl border border-slate-100 shadow-sm p-4">
        <form method="GET" action="{{ route('purchase-requests.index') }}" class="grid grid-cols-1 md:grid-cols-4 gap-4">
            <div class="md:col-span-2">
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Buscar por código o justificación..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
            </div>
            <div>
                <select name="status" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    <option value="">Todos los estados</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                    <option value="pending" {{ request('status') === 'pending' ? 'selected' : '' }}>Pendiente</option>
                    <option value="approved" {{ request('status') === 'approved' ? 'selected' : '' }}>Aprobada</option>
                    <option value="rejected" {{ request('status') === 'rejected' ? 'selected' : '' }}>Rechazada</option>
                </select>
            </div>
            <div class="flex gap-2">
                <button type="submit" class="w-full bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold py-2.5 px-4 rounded-xl text-sm transition-all">Filtrar</button>
                @if(request('search') || request('status'))
                    <a href="{{ route('purchase-requests.index') }}" class="bg-slate-100 hover:bg-slate-200 text-slate-600 font-bold py-2.5 px-4 rounded-xl text-sm transition-all">Limpiar</a>
                @endif
            </div>
        </form>
    </div>
    <!-- Listado -->
    <section class="overflow-x-auto">
        <table class="w-full text-left border-separate border-spacing-x-0 border-spacing-y-3">
            <thead>
                <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider">
                    <th class="py-3 px-6">Código</th>
                    <th class="py-3 px-6">Fecha</th>
                    <th class="py-3 px-6">Sucursal / Bodega</th>
                    <th class="py-3 px-6">Solicitante</th>
                    <th class="py-3 px-6 text-center">Productos</th>
                    <th class="py-3 px-6 text-center">Estado</th>
                    <th class="py-3 px-6 text-center">Acciones</th>
                </tr>
            </thead>
            <tbody>
                @forelse($purchaseRequests as $purchaseRequest)
                    <tr class="group hover:scale-[1.001] hover:shadow-md transition-all duration-200">
                        <td class="py-4 px-6 bg-white rounded-l-2xl border-l border-y border-slate-100">
                            <div class="font-extrabold text-[#005e66] text-sm">{{ $purchaseRequest->purchase_request_code }}</div>
                            <div class="text-xs text-slate-400 mt-0.5">{{ $purchaseRequest->created_at?->format('d/m/Y H:i') }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm">
                            <div class="font-bold text-slate-700">{{ $purchaseRequest->request_date?->format('d/m/Y') }}</div>
                            <div class="text-xs text-slate-400">Requerida: {{ $purchaseRequest->required_date?->format('d/m/Y') }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-sm">
                            <div class="font-bold text-slate-700">{{ $purchaseRequest->branch?->name ?? 'Sin sucursal' }}</div>
                            <div class="text-xs text-slate-400 font-semibold">{{ $purchaseRequest->warehouse?->name ?? 'Sin bodega' }}</div>
                        </td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100"><div class="font-semibold text-slate-700 text-sm">{{ $purchaseRequest->user?->name ?? 'Usuario no disponible' }}</div></td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center"><span class="inline-flex items-center justify-center min-w-8 px-2.5 py-1 rounded-full bg-sky-50 text-sky-700 text-xs font-extrabold">{{ $purchaseRequest->details->count() }}</span></td>
                        <td class="py-4 px-6 bg-white border-y border-slate-100 text-center">
                            @if($purchaseRequest->status === 'draft')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">● Borrador</span>
                            @elseif($purchaseRequest->status === 'pending')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">● Pendiente</span>
                            @elseif($purchaseRequest->status === 'approved')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">● Aprobada</span>
                            @elseif($purchaseRequest->status === 'rejected')
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">● Rechazada</span>
                            @else
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">{{ ucfirst($purchaseRequest->status) }}</span>
                            @endif
                        </td>
                        <td class="py-4 px-6 bg-white rounded-r-2xl border-r border-y border-slate-100 text-center">
                            <div class="flex items-center justify-center gap-2">
                                <button type="button" onclick="openModal('show-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}')" class="p-2 rounded-lg bg-sky-50 text-sky-600 hover:bg-sky-100 transition-all" title="Ver detalles">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                </button>
                                @if($purchaseRequest->status === 'draft')
                                    <button type="button" onclick="openModal('edit-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}')" class="p-2 rounded-lg bg-amber-50 text-amber-600 hover:bg-amber-100 transition-all" title="Editar">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <form action="{{ route('purchase-requests.destroy', $purchaseRequest) }}" method="POST" onsubmit="return confirm('¿Desea eliminar esta solicitud de compra?');">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="p-2 rounded-lg bg-rose-50 text-rose-600 hover:bg-rose-100 transition-all" title="Eliminar">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr><td colspan="7" class="py-12 text-center text-slate-400 font-semibold">No hay solicitudes de compra registradas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </section>
    @if($purchaseRequests->hasPages())
        <div class="mt-6">{{ $purchaseRequests->links() }}</div>
    @endif
</div>

<!-- MODAL CREAR -->
<div id="create-purchase-request-modal" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 flex items-start sm:items-center justify-center">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-6xl w-full shadow-2xl mx-4 my-auto max-h-[90vh] overflow-y-auto border border-slate-100 relative">
        <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-white z-20 border-b border-slate-100 flex items-center justify-between mb-6 shadow-sm rounded-t-3xl">
            <div>
                <h3 class="text-xl font-extrabold text-slate-800">Nueva Solicitud de Compra</h3>
                <p class="text-xs text-slate-400">Complete los datos generales y agregue los productos requeridos.</p>
            </div>
            <button type="button" onclick="closeModal('create-purchase-request-modal')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">✕</button>
        </div>
        <form action="{{ route('purchase-requests.store') }}" method="POST" class="space-y-6">
            @csrf
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-4">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">1. Información General</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sucursal *</label>
                        <select name="id_branch" id="create_id_branch" required onchange="filterWarehouses(this, document.getElementById('create_id_warehouse'))" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Seleccione sucursal...</option>
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ old('id_branch') == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Bodega *</label>
                        <select name="id_warehouse" id="create_id_warehouse" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            <option value="">Seleccione bodega...</option>
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" data-branch="{{ $warehouse->branch_id }}" {{ old('id_warehouse') == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fecha de Solicitud *</label>
                        <input type="datetime-local" name="request_date" value="{{ old('request_date', now()->format('Y-m-d\TH:i')) }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fecha Requerida *</label>
                        <input type="datetime-local" name="required_date" value="{{ old('required_date') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Justificación *</label>
                    <textarea name="justification" rows="3" required placeholder="Explique el motivo de la solicitud..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('justification') }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Notas</label>
                    <textarea name="notes" rows="2" placeholder="Observaciones adicionales..." class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ old('notes') }}</textarea>
                </div>
            </div>
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-4">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3">
                    <div>
                        <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">2. Detalle de Productos</h4>
                        <p class="text-xs text-slate-400 mt-1">Agregue uno o varios productos a la solicitud.</p>
                    </div>
                    <button type="button" onclick="addPurchaseRequestRow()" class="bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold px-4 py-2.5 rounded-xl text-xs transition-all">+ Agregar Producto</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px]">
                        <thead>
                            <tr class="text-[10px] font-extrabold text-slate-400 uppercase tracking-wider border-b border-slate-200">
                                <th class="text-left py-3 px-2">Producto</th>
                                <th class="text-left py-3 px-2 w-28">Cantidad</th>
                                <th class="text-left py-3 px-2 w-44">Unidad</th>
                                <th class="text-left py-3 px-2">Descripción</th>
                                <th class="text-left py-3 px-2">Notas</th>
                                <th class="text-center py-3 px-2 w-16">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="purchase-request-details"></tbody>
                    </table>
                </div>
            </div>
            <div class="flex items-center justify-end gap-3 pt-6 border-t border-slate-200">
                <button type="button" onclick="closeModal('create-purchase-request-modal')" class="px-6 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-100 transition">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] text-white font-bold text-sm hover:bg-[#3cb0a4] transition-all shadow-md">Guardar Solicitud</button>
            </div>
        </form>
    </div>
</div>

<!-- MODALES VER -->
@foreach($purchaseRequests as $purchaseRequest)
<div id="show-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 flex items-start sm:items-center justify-center">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-5xl w-full shadow-2xl mx-4 my-auto max-h-[90vh] overflow-y-auto border border-slate-100 relative">
        <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-white z-20 border-b border-slate-100 flex items-center justify-between mb-6 shadow-sm rounded-t-3xl">
            <div>
                <div class="flex items-center gap-2">
                    <h3 class="text-xl font-extrabold text-slate-800">{{ $purchaseRequest->purchase_request_code }}</h3>
                    @if($purchaseRequest->status === 'draft')
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-slate-100 text-slate-600">Borrador</span>
                    @elseif($purchaseRequest->status === 'pending')
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-amber-100 text-amber-700">Pendiente</span>
                    @elseif($purchaseRequest->status === 'approved')
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700">Aprobada</span>
                    @else
                        <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-rose-100 text-rose-700">Rechazada</span>
                    @endif
                </div>
                <p class="text-xs text-slate-400 mt-1">Detalle completo de la solicitud de compra.</p>
            </div>
            <button type="button" onclick="closeModal('show-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">✕</button>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
            <div class="bg-slate-50 rounded-2xl p-4"><span class="text-[10px] uppercase font-bold text-slate-400">Sucursal</span><p class="font-bold text-slate-700 mt-1">{{ $purchaseRequest->branch?->name ?? 'N/A' }}</p></div>
            <div class="bg-slate-50 rounded-2xl p-4"><span class="text-[10px] uppercase font-bold text-slate-400">Bodega</span><p class="font-bold text-slate-700 mt-1">{{ $purchaseRequest->warehouse?->name ?? 'N/A' }}</p></div>
            <div class="bg-slate-50 rounded-2xl p-4"><span class="text-[10px] uppercase font-bold text-slate-400">Solicitante</span><p class="font-bold text-slate-700 mt-1">{{ $purchaseRequest->user?->name ?? 'N/A' }}</p></div>
            <div class="bg-slate-50 rounded-2xl p-4"><span class="text-[10px] uppercase font-bold text-slate-400">Fecha requerida</span><p class="font-bold text-slate-700 mt-1">{{ $purchaseRequest->required_date?->format('d/m/Y H:i') }}</p></div>
        </div>
        <div class="bg-slate-50 rounded-2xl p-4 mb-6">
            <span class="text-[10px] uppercase font-bold text-slate-400">Justificación</span>
            <p class="text-sm text-slate-700 mt-2">{{ $purchaseRequest->justification }}</p>
            @if($purchaseRequest->notes)
                <div class="mt-4 pt-4 border-t border-slate-200"><span class="text-[10px] uppercase font-bold text-slate-400">Notas</span><p class="text-sm text-slate-700 mt-1">{{ $purchaseRequest->notes }}</p></div>
            @endif
        </div>
        <div class="overflow-x-auto mb-6">
            <table class="w-full">
                <thead>
                    <tr class="text-[10px] font-extrabold text-slate-400 uppercase border-b border-slate-200">
                        <th class="text-left py-3 px-3">Producto</th>
                        <th class="text-center py-3 px-3">Cantidad</th>
                        <th class="text-left py-3 px-3">Unidad</th>
                        <th class="text-left py-3 px-3">Descripción</th>
                        <th class="text-left py-3 px-3">Notas</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($purchaseRequest->details as $detail)
                        <tr class="border-b border-slate-100">
                            <td class="py-3 px-3 text-sm font-bold text-slate-700">{{ $detail->product?->name ?? 'Producto no disponible' }}</td>
                            <td class="py-3 px-3 text-sm text-center text-slate-700">{{ rtrim(rtrim($detail->quantity, '0'), '.') }}</td>
                            <td class="py-3 px-3 text-sm text-slate-600">{{ $detail->unit?->abbreviation ?: ($detail->unit?->name ?? 'N/A') }}</td>
                            <td class="py-3 px-3 text-sm text-slate-600">{{ $detail->description ?: '—' }}</td>
                            <td class="py-3 px-3 text-sm text-slate-600">{{ $detail->notes ?: '—' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        <div class="flex flex-wrap items-center justify-end gap-3 pt-5 border-t border-slate-200">
            @if($purchaseRequest->status === 'draft')
                <form method="POST" action="{{ route('purchase-requests.update-status', $purchaseRequest) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="pending">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-amber-500 hover:bg-amber-600 text-white font-bold text-sm transition">Enviar a Aprobación</button>
                </form>
            @elseif($purchaseRequest->status === 'pending')
                <form method="POST" action="{{ route('purchase-requests.update-status', $purchaseRequest) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="rejected">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-sm transition">Rechazar</button>
                </form>
                <form method="POST" action="{{ route('purchase-requests.update-status', $purchaseRequest) }}">
                    @csrf
                    @method('PATCH')
                    <input type="hidden" name="status" value="approved">
                    <button type="submit" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition">Aprobar</button>
                </form>
            @endif
        </div>
    </div>
</div>
@endforeach

<!-- MODALES EDITAR -->
@foreach($purchaseRequests as $purchaseRequest)
@if($purchaseRequest->status === 'draft')
<div id="edit-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}" class="hidden fixed inset-0 z-50 overflow-y-auto bg-slate-900/60 backdrop-blur-sm p-4 sm:p-6 flex items-start sm:items-center justify-center">
    <div class="bg-white rounded-3xl p-6 sm:p-8 max-w-6xl w-full shadow-2xl mx-4 my-auto max-h-[90vh] overflow-y-auto border border-slate-100 relative">
        <div class="sticky -top-6 -mx-6 -mt-6 sm:-top-8 sm:-mx-8 sm:-mt-8 p-6 bg-white z-20 border-b border-slate-100 flex items-center justify-between mb-6 shadow-sm rounded-t-3xl">
            <div><h3 class="text-xl font-extrabold text-slate-800">Editar {{ $purchaseRequest->purchase_request_code }}</h3><p class="text-xs text-slate-400">Modifique los datos mientras la solicitud permanezca en borrador.</p></div>
            <button type="button" onclick="closeModal('edit-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}')" class="p-2 rounded-xl text-slate-400 hover:text-slate-600 hover:bg-slate-100 transition">✕</button>
        </div>
        <form action="{{ route('purchase-requests.update', $purchaseRequest) }}" method="POST" class="space-y-6">
            @csrf
            @method('PUT')
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-4">
                <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">1. Información General</h4>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Sucursal *</label>
                        <select name="id_branch" id="edit_branch_{{ $purchaseRequest->id_purchase_request }}" required onchange="filterWarehouses(this, document.getElementById('edit_warehouse_{{ $purchaseRequest->id_purchase_request }}'))" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            @foreach($branches as $branch)
                                <option value="{{ $branch->id }}" {{ $purchaseRequest->id_branch == $branch->id ? 'selected' : '' }}>{{ $branch->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Bodega *</label>
                        <select name="id_warehouse" id="edit_warehouse_{{ $purchaseRequest->id_purchase_request }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                            @foreach($warehouses as $warehouse)
                                <option value="{{ $warehouse->id }}" data-branch="{{ $warehouse->branch_id }}" {{ $purchaseRequest->id_warehouse == $warehouse->id ? 'selected' : '' }}>{{ $warehouse->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fecha de Solicitud *</label>
                        <input type="datetime-local" name="request_date" value="{{ $purchaseRequest->request_date?->format('Y-m-d\TH:i') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Fecha Requerida *</label>
                        <input type="datetime-local" name="required_date" value="{{ $purchaseRequest->required_date?->format('Y-m-d\TH:i') }}" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Justificación *</label>
                    <textarea name="justification" rows="3" required class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ $purchaseRequest->justification }}</textarea>
                </div>
                <div>
                    <label class="block text-xs font-bold text-slate-600 uppercase mb-1">Notas</label>
                    <textarea name="notes" rows="2" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">{{ $purchaseRequest->notes }}</textarea>
                </div>
            </div>
            <div class="bg-slate-50/70 p-4 rounded-2xl border border-slate-100 space-y-4">
                <div class="flex items-center justify-between">
                    <h4 class="text-xs font-bold text-[#005e66] uppercase tracking-wider">2. Detalle de Productos</h4>
                    <button type="button" onclick="addPurchaseRequestRow('edit-details-{{ $purchaseRequest->id_purchase_request }}')" class="bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold px-4 py-2 rounded-xl text-xs transition">+ Agregar Producto</button>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full min-w-[1050px]">
                        <thead>
                            <tr class="text-[10px] font-extrabold text-slate-400 uppercase border-b border-slate-200">
                                <th class="text-left py-3 px-2">Producto</th>
                                <th class="text-left py-3 px-2">Cantidad</th>
                                <th class="text-left py-3 px-2">Unidad</th>
                                <th class="text-left py-3 px-2">Descripción</th>
                                <th class="text-left py-3 px-2">Notas</th>
                                <th class="text-center py-3 px-2">Acción</th>
                            </tr>
                        </thead>
                        <tbody id="edit-details-{{ $purchaseRequest->id_purchase_request }}">
                            @foreach($purchaseRequest->details as $index => $detail)
                                <tr class="purchase-detail-row">
                                    <td class="p-2">
                                        <select name="details[{{ $index }}][id_product]" onchange="setPurchaseUnit(this)" required class="product-select w-full px-3 py-2 rounded-xl border border-slate-200 text-sm">
                                            <option value="">Producto...</option>
                                            @foreach($products as $product)
                                                <option value="{{ $product->id }}" data-unit="{{ $product->purchase_unit }}" {{ $detail->id_product == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-2"><input type="number" step="0.0001" min="0.0001" name="details[{{ $index }}][quantity]" value="{{ $detail->quantity }}" required class="w-28 px-3 py-2 rounded-xl border border-slate-200 text-sm"></td>
                                    <td class="p-2">
                                        <select name="details[{{ $index }}][id_unit]" required class="unit-select w-40 px-3 py-2 rounded-xl border border-slate-200 text-sm">
                                            <option value="">Unidad...</option>
                                            @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ $detail->id_unit == $unit->id ? 'selected' : '' }}>{{ $unit->abbreviation ?: $unit->name }}</option>
                                            @endforeach
                                        </select>
                                    </td>
                                    <td class="p-2"><input type="text" name="details[{{ $index }}][description]" value="{{ $detail->description }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm"></td>
                                    <td class="p-2"><input type="text" name="details[{{ $index }}][notes]" value="{{ $detail->notes }}" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm"></td>
                                    <td class="p-2 text-center"><button type="button" onclick="removePurchaseRequestRow(this)" class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg">✕</button></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="flex justify-end gap-3 pt-6 border-t border-slate-200">
                <button type="button" onclick="closeModal('edit-purchase-request-modal-{{ $purchaseRequest->id_purchase_request }}')" class="px-6 py-2.5 rounded-xl bg-white border border-slate-200 text-slate-600 font-bold text-sm hover:bg-slate-100">Cancelar</button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold text-sm shadow-md">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>
@endif
@endforeach

<!-- Plantilla de fila dinámica -->
<template id="purchase-request-row-template">
    <tr class="purchase-detail-row">
        <td class="p-2">
            <select data-field="id_product" onchange="setPurchaseUnit(this)" required class="product-select w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                <option value="">Seleccione producto...</option>
                @foreach($products as $product)
                    <option value="{{ $product->id }}" data-unit="{{ $product->purchase_unit }}">{{ $product->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="p-2"><input type="number" data-field="quantity" step="0.0001" min="0.0001" required placeholder="0" class="w-28 px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"></td>
        <td class="p-2">
            <select data-field="id_unit" required class="unit-select w-40 px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]">
                <option value="">Unidad...</option>
                @foreach($units as $unit)
                    <option value="{{ $unit->id }}">{{ $unit->abbreviation ?: $unit->name }}</option>
                @endforeach
            </select>
        </td>
        <td class="p-2"><input type="text" data-field="description" placeholder="Descripción..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"></td>
        <td class="p-2"><input type="text" data-field="notes" placeholder="Notas..." class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:border-[#005e66]"></td>
        <td class="p-2 text-center"><button type="button" onclick="removePurchaseRequestRow(this)" class="p-2 bg-rose-50 text-rose-600 hover:bg-rose-100 rounded-lg transition" title="Eliminar producto">✕</button></td>
    </tr>
</template>

<script>
const purchaseRequestRowCounters = {};
function addPurchaseRequestRow(containerId = 'purchase-request-details') {
    const container = document.getElementById(containerId);
    const template = document.getElementById('purchase-request-row-template');
    if (!container || !template) return;
    if (purchaseRequestRowCounters[containerId] === undefined) {
        purchaseRequestRowCounters[containerId] = container.querySelectorAll('.purchase-detail-row').length;
    }
    const index = purchaseRequestRowCounters[containerId]++;
    const clone = template.content.cloneNode(true);
    clone.querySelectorAll('[data-field]').forEach(element => {
        const field = element.getAttribute('data-field');
        element.name = `details[${index}][${field}]`;
    });
    container.appendChild(clone);
}
function removePurchaseRequestRow(button) {
    const row = button.closest('.purchase-detail-row');
    if (!row) return;
    const tbody = row.closest('tbody');
    const rows = tbody.querySelectorAll('.purchase-detail-row');
    if (rows.length <= 1) {
        alert('La solicitud debe contener al menos un producto.');
        return;
    }
    row.remove();
}
function setPurchaseUnit(productSelect) {
    const selectedOption = productSelect.options[productSelect.selectedIndex];
    if (!selectedOption) return;
    const unitId = selectedOption.dataset.unit;
    const row = productSelect.closest('.purchase-detail-row');
    if (!row) return;
    const unitSelect = row.querySelector('.unit-select');
    if (!unitSelect) return;
    unitSelect.value = unitId || '';
}
function filterWarehouses(branchSelect, warehouseSelect) {
    if (!branchSelect || !warehouseSelect) return;
    const branchId = branchSelect.value;
    const options = warehouseSelect.querySelectorAll('option[data-branch]');
    let selectedStillValid = false;
    options.forEach(option => {
        const belongsToBranch = branchId && option.dataset.branch === branchId;
        option.style.display = belongsToBranch ? '' : 'none';
        option.disabled = !belongsToBranch;
        if (option.selected && belongsToBranch) selectedStillValid = true;
    });
    if (!selectedStillValid) warehouseSelect.value = '';
}
document.addEventListener('DOMContentLoaded', function () {
    const createBranch = document.getElementById('create_id_branch');
    const createWarehouse = document.getElementById('create_id_warehouse');
    if (createBranch && createWarehouse) filterWarehouses(createBranch, createWarehouse);
    const createDetails = document.getElementById('purchase-request-details');
    if (createDetails && createDetails.children.length === 0) addPurchaseRequestRow();
    document.querySelectorAll('[id^="edit_branch_"]').forEach(branchSelect => {
        const requestId = branchSelect.id.replace('edit_branch_', '');
        const warehouseSelect = document.getElementById('edit_warehouse_' + requestId);
        if (warehouseSelect) filterWarehouses(branchSelect, warehouseSelect);
    });
    @if(session('open_create_modal'))
        openModal('create-purchase-request-modal');
    @endif
    @if($errors->any())
        openModal('create-purchase-request-modal');
    @endif
    @if(request()->filled('show'))
        openModal('show-purchase-request-modal-{{ request('show') }}');
    @endif
    @if(request()->filled('edit'))
        openModal('edit-purchase-request-modal-{{ request('edit') }}');
    @endif
});
</script>
@endsection