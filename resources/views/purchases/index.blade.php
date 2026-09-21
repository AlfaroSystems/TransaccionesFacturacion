@extends('layouts.app')
@section('title', 'Facturas y Recepciones de Compra')

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
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg">Compras</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">Módulo de Facturas y Recepciones</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                Facturas y Compras
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Consulte y gestione las facturas comerciales y recepciones de mercancía asociadas a Órdenes de Compra.
            </p>
        </div>
        @can('purchases.crear')
            <button type="button" onclick="openCreatePurchaseModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#005e66] hover:bg-[#00474f] text-white font-bold rounded-xl shadow-md transition-all text-sm transform hover:-translate-y-0.5 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nueva Compra</span>
            </button>
        @endcan
    </div>

    @if(session('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-800 px-5 py-4 rounded-2xl font-semibold text-sm shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-emerald-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
            <span>{{ session('success') }}</span>
        </div>
    @endif

    @if(session('error'))
        <div class="bg-rose-50 border border-rose-200 text-rose-800 px-5 py-4 rounded-2xl font-semibold text-sm shadow-sm flex items-center gap-2">
            <svg class="w-5 h-5 text-rose-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <span>{{ session('error') }}</span>
        </div>
    @endif

    @if($errors->any())
        <div class="bg-rose-50 dark:bg-rose-950/40 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-5 py-4 rounded-2xl text-xs font-semibold space-y-1 shadow-sm">
            <div class="flex items-center gap-2 font-bold text-sm text-rose-800 dark:text-rose-200">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>Por favor revise los errores encontrados:</span>
            </div>
            <ul class="list-disc pl-8 space-y-0.5">
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <!-- Tarjetas de Métricas -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Compras</p>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">{{ $totalCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 flex items-center justify-center text-xl font-bold">
                🧾
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Borradores</p>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">{{ $draftCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-amber-50 dark:bg-amber-900/40 text-amber-600 dark:text-amber-300 flex items-center justify-center text-xl font-bold">
                ✎
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Recibidas / Completadas</p>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">{{ $completedCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-xl font-bold">
                ✓
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Monto Total</p>
                <p class="text-2xl font-extrabold text-[#005e66] dark:text-teal-400 mt-1">${{ number_format($totalAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-xl font-bold">
                💲
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 p-5 shadow-sm">
        <form method="GET" action="{{ route('purchases.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Código, factura de proveedor, OC..." class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Proveedor</label>
                <select name="id_supplier" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    <option value="">Todos los proveedores</option>
                    @foreach($suppliers as $sup)
                        <option value="{{ $sup->id_supplier }}" {{ request('id_supplier') == $sup->id_supplier ? 'selected' : '' }}>
                            {{ $sup->name }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Estado</label>
                <select name="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    <option value="">Todos los estados</option>
                    <option value="draft" {{ request('status') === 'draft' ? 'selected' : '' }}>Borrador</option>
                    <option value="received" {{ request('status') === 'received' ? 'selected' : '' }}>Recibida</option>
                    <option value="completed" {{ request('status') === 'completed' ? 'selected' : '' }}>Completada</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelada</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full bg-[#005e66] hover:bg-[#00474f] text-white font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-sm">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'id_supplier', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('purchases.index') }}" class="p-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-600 dark:text-slate-300 rounded-xl" title="Limpiar filtros">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Compras -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                    <tr>
                        <th class="py-4 px-6">Código / Factura</th>
                        <th class="py-4 px-6">Orden de Compra</th>
                        <th class="py-4 px-6">Proveedor</th>
                        <th class="py-4 px-6">Fecha Compra</th>
                        <th class="py-4 px-6">Destino</th>
                        <th class="py-4 px-6 text-right">Total</th>
                        <th class="py-4 px-6 text-center">Estado</th>
                        <th class="py-4 px-6 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($purchases as $purchase)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-extrabold text-[#005e66] dark:text-teal-400 block font-mono">
                                    {{ $purchase->purchase_code }}
                                </span>
                                @if($purchase->supplier_invoice_number)
                                    <span class="text-xs text-slate-500 dark:text-slate-400 flex items-center gap-1 mt-0.5">
                                        📄 Fac: <strong class="text-slate-700 dark:text-slate-300">{{ $purchase->supplier_invoice_number }}</strong>
                                    </span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                @if($purchase->purchaseOrder)
                                    <a href="{{ route('purchase_orders.show', $purchase->purchaseOrder->id_purchase_order) }}" class="font-semibold text-sky-600 dark:text-sky-400 hover:underline font-mono text-xs">
                                        {{ $purchase->purchaseOrder->purchase_order_code }}
                                    </a>
                                @else
                                    <span class="text-slate-400 text-xs">N/A</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">
                                    {{ $purchase->supplier->name ?? 'Proveedor no asignado' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                <span class="text-slate-700 dark:text-slate-300 text-xs font-semibold">
                                    {{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y') : '-' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-400">
                                <div>{{ $purchase->branch->name ?? 'Sucursal principal' }}</div>
                                <div class="text-[11px] text-slate-400">{{ $purchase->warehouse->name ?? 'Bodega' }}</div>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-extrabold text-slate-800 dark:text-slate-100 font-mono text-sm">
                                    ${{ number_format($purchase->total, 2) }}
                                </span>
                                <span class="text-[11px] text-slate-400 block">{{ $purchase->currency }}</span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold border {{ $statusClasses[$purchase->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    ● {{ $statusNames[$purchase->status] ?? ucfirst($purchase->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @can('purchases.ver')
                                        <a href="{{ route('purchases.show', $purchase->id_purchase) }}" class="p-2 rounded-xl hover:bg-teal-50 dark:hover:bg-teal-950/50 text-[#005e66] dark:text-teal-300 transition-colors" title="Ver Detalle">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    @endcan
                                    @if($purchase->status === 'draft')
                                        @can('purchases.editar')
                                            <button type="button" onclick="openEditPurchaseModal({{ $purchase->id_purchase }})" class="p-2 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-950/50 text-amber-600 dark:text-amber-400 transition-colors cursor-pointer" title="Editar Compra (Modal)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        @endcan
                                        @can('purchases.eliminar')
                                            <button type="button" onclick="openDeletePurchaseModal('{{ route('purchases.destroy', $purchase->id_purchase) }}', '{{ $purchase->purchase_code }}')" class="p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 dark:text-rose-400 transition-colors cursor-pointer" title="Eliminar Compra">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        @endcan
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="8" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                <div class="text-4xl mb-2">🧾</div>
                                <p class="font-bold">No se encontraron facturas o compras registradas.</p>
                                <p class="text-xs mt-1">Haga clic en "Nueva Compra" para registrar una a partir de una Orden de Compra.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($purchases->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                {{ $purchases->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE CREACIÓN / EDICIÓN DE COMPRA      -->
<!-- ========================================== -->
<div id="purchaseModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-5xl w-full max-h-[92vh] flex flex-col overflow-hidden my-auto transform transition-all animate-scale-up">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/80">
            <div>
                <span id="purchaseModalBadge" class="px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-md">Nueva Compra</span>
                <h2 id="purchaseModalTitle" class="text-lg font-extrabold text-slate-800 dark:text-slate-100 mt-0.5">
                    Registrar Factura / Recepción de Compra
                </h2>
                <p id="purchaseModalSubtitle" class="text-xs text-slate-400">
                    Seleccione la orden de compra aprobada para importar los productos.
                </p>
            </div>
            <button type="button" onclick="closePurchaseModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-200 flex items-center justify-center transition-colors">
                ✕
            </button>
        </div>

        <!-- Modal Form -->
        <form id="purchaseModalForm" method="POST" action="{{ route('purchases.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="_method" id="purchaseModalMethod" value="POST">
            <input type="hidden" name="id_purchase_order" id="modalHiddenOrderId" value="">

            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Selector de Orden de Compra (Modo Crear) -->
                <div id="modalOrderSelectContainer" class="bg-indigo-50/80 dark:bg-slate-900/60 border border-indigo-200 dark:border-slate-700 rounded-2xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="modalOrderSelect" class="text-xs font-extrabold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>📋 Orden de Compra Asociada *</span>
                        </label>
                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400">Solo órdenes emitidas o recibidas parciales</span>
                    </div>
                    <select id="modalOrderSelect" class="w-full px-3.5 py-2 rounded-xl border border-indigo-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66] outline-none">
                        <option value="">-- Seleccione una orden de compra aprobada --</option>
                        @foreach($orders as $ord)
                            <option value="{{ $ord->id_purchase_order }}">
                                {{ $ord->purchase_order_code }} - {{ $ord->supplier->name ?? 'Sin Proveedor' }} (${{ number_format($ord->total, 2) }}) [{{ ucfirst($ord->status) }}]
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Resumen de Orden (Modo Editar) -->
                <div id="modalOrderInfoBox" class="hidden bg-slate-100 dark:bg-slate-900/50 rounded-xl p-3 text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        Orden de Compra: <strong id="lblModalOrderCode" class="font-mono text-sm text-[#005e66] dark:text-teal-400"></strong>
                    </div>
                    <div>
                        Código de Factura: <strong id="lblModalPurchaseCode" class="font-mono text-sm text-slate-800 dark:text-slate-100"></strong>
                    </div>
                </div>

                <!-- Cabecera de Compra -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Factura Proveedor</label>
                        <input type="text" name="supplier_invoice_number" id="modal_supplier_invoice_number" placeholder="Ej: FAC-001-987" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Factura Proveedor</label>
                        <input type="date" name="supplier_invoice_date" id="modal_supplier_invoice_date" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Recepción *</label>
                        <input type="datetime-local" name="purchase_date" id="modal_purchase_date" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado *</label>
                        <select name="status" id="modal_status" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="completed">Completada</option>
                            <option value="received">Recibida</option>
                            <option value="draft">Borrador</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Proveedor *</label>
                        <select name="id_supplier" id="modal_id_supplier" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="">-- Seleccione proveedor --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id_supplier }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Sucursal</label>
                        <select name="id_branch" id="modal_id_branch" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="">-- Sucursal --</option>
                            @foreach($branches as $br)
                                <option value="{{ $br->id }}">{{ $br->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Bodega Destino</label>
                        <select name="id_warehouse" id="modal_id_warehouse" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="">-- Bodega --</option>
                            @foreach($warehouses as $wh)
                                <option value="{{ $wh->id }}">{{ $wh->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Moneda</label>
                        <input type="text" name="currency" id="modal_currency" value="USD" maxlength="3" class="w-full uppercase px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Notas u Observaciones</label>
                    <textarea name="notes" id="modal_notes" rows="2" placeholder="Observaciones sobre la recepción física o factura..." class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]"></textarea>
                </div>

                <!-- Tabla de Líneas -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-900/60 px-4 py-2.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase text-slate-600 dark:text-slate-300">Líneas de Productos a Recibir</span>
                        <span class="text-[11px] text-slate-400">Verifique cantidades y montos antes de guardar</span>
                    </div>
                    <div class="overflow-x-auto max-h-60">
                        <table class="w-full text-left text-xs" id="modalItemsTable">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-2 text-center w-20">Pedida</th>
                                    <th class="py-2.5 px-2 text-center w-24">Recibida *</th>
                                    <th class="py-2.5 px-2 w-24">Precio *</th>
                                    <th class="py-2.5 px-2 w-20">Desc. ($)</th>
                                    <th class="py-2.5 px-2 w-16">% IVA</th>
                                    <th class="py-2.5 px-3 text-right w-24">Subtotal</th>
                                    <th class="py-2.5 px-3 text-right w-20">IVA</th>
                                    <th class="py-2.5 px-3 text-right w-24">Total</th>
                                </tr>
                            </thead>
                            <tbody id="modalItemsBody" class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                <tr>
                                    <td colspan="9" class="py-8 text-center text-slate-400">
                                        Seleccione una orden de compra en la parte superior para cargar los productos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/60 p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <div class="w-full sm:w-72 space-y-1.5 text-xs font-semibold">
                            <div class="flex justify-between text-slate-500">
                                <span>Subtotal:</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblSubtotal">$0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Descuento:</span>
                                <span class="font-mono font-bold text-rose-500" id="modalLblDiscount">-$0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Impuestos (IVA):</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblTax">$0.00</span>
                            </div>
                            <div class="flex justify-between text-sm font-extrabold text-slate-800 dark:text-slate-100 pt-1.5 border-t border-slate-200 dark:border-slate-700">
                                <span>Total General:</span>
                                <span class="font-mono text-[#005e66] dark:text-teal-400 text-base" id="modalLblTotal">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-3">
                <button type="button" onclick="closePurchaseModal()" class="px-5 py-2 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitPurchaseModal" class="px-6 py-2 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white font-bold text-xs shadow-md transition-all">
                    Guardar Compra
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN       -->
<!-- ========================================== -->
<div id="deletePurchaseModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-md w-full p-6 text-center animate-scale-up">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-300 flex items-center justify-center text-2xl mb-4">
            ⚠️
        </div>
        <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-100 mb-1" id="deletePurchaseTitle">
            ¿Eliminar Compra?
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6" id="deletePurchaseDesc">
            Esta acción eliminará de forma permanente la compra en borrador y todos sus registros de detalle asociados.
        </p>
        <form id="deletePurchaseForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeletePurchaseModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-rose-600 hover:bg-rose-700 text-white font-bold text-xs shadow-md transition-colors">
                    Sí, Eliminar Permanentemente
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    // Referencias del modal de compra
    const purchaseModal            = document.getElementById('purchaseModal');
    const purchaseModalForm        = document.getElementById('purchaseModalForm');
    const purchaseModalBadge       = document.getElementById('purchaseModalBadge');
    const purchaseModalTitle       = document.getElementById('purchaseModalTitle');
    const purchaseModalSubtitle    = document.getElementById('purchaseModalSubtitle');
    const purchaseModalMethod      = document.getElementById('purchaseModalMethod');
    const btnSubmitPurchaseModal   = document.getElementById('btnSubmitPurchaseModal');
    const modalOrderSelectContainer= document.getElementById('modalOrderSelectContainer');
    const modalOrderInfoBox        = document.getElementById('modalOrderInfoBox');
    const modalOrderSelect         = document.getElementById('modalOrderSelect');
    const modalHiddenOrderId       = document.getElementById('modalHiddenOrderId');
    const modalItemsBody           = document.getElementById('modalItemsBody');

    // Apertura en modo Creación
    function openCreatePurchaseModal() {
        purchaseModalBadge.textContent    = 'Nueva Compra';
        purchaseModalBadge.className      = 'px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-md';
        purchaseModalTitle.textContent    = 'Registrar Factura / Recepción de Compra';
        purchaseModalSubtitle.textContent = 'Seleccione una Orden de Compra aprobada para importar los productos a recibir.';
        btnSubmitPurchaseModal.textContent= 'Registrar Compra';

        purchaseModalForm.action = "{{ route('purchases.store') }}";
        purchaseModalMethod.value = "POST";

        modalOrderSelectContainer.classList.remove('hidden');
        modalOrderInfoBox.classList.add('hidden');
        modalOrderSelect.value = '';
        modalHiddenOrderId.value = '';

        // Reset de campos de cabecera
        document.getElementById('modal_supplier_invoice_number').value = '';
        document.getElementById('modal_supplier_invoice_date').value   = '';
        document.getElementById('modal_purchase_date').value           = new Date().toISOString().slice(0, 16);
        document.getElementById('modal_status').value                  = 'completed';
        document.getElementById('modal_id_supplier').value             = '';
        document.getElementById('modal_id_branch').value               = '';
        document.getElementById('modal_id_warehouse').value            = '';
        document.getElementById('modal_currency').value                = 'USD';
        document.getElementById('modal_notes').value                   = '';

        modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Seleccione una orden de compra en la parte superior para cargar los productos.</td></tr>';
        recalcularTotalesModal();

        purchaseModal.classList.remove('hidden');
        purchaseModal.classList.add('flex');
    }

    // Apertura en modo Edición
    function openEditPurchaseModal(purchaseId) {
        purchaseModalBadge.textContent    = 'Modo Edición';
        purchaseModalBadge.className      = 'px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-800 rounded-md';
        purchaseModalTitle.textContent    = 'Editar Factura / Compra';
        purchaseModalSubtitle.textContent = 'Modifique los datos de cabecera o cantidades recibidas de la compra en borrador.';
        btnSubmitPurchaseModal.textContent= 'Actualizar Compra';

        purchaseModalForm.action = `/purchases/${purchaseId}`;
        purchaseModalMethod.value = "PUT";

        modalOrderSelectContainer.classList.add('hidden');
        modalOrderInfoBox.classList.remove('hidden');

        modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Cargando datos de la compra...</td></tr>';
        purchaseModal.classList.remove('hidden');
        purchaseModal.classList.add('flex');

        fetch(`/purchases/${purchaseId}/edit-data`)
            .then(res => {
                if (!res.ok) throw new Error('Error al consultar datos');
                return res.json();
            })
            .then(data => {
                document.getElementById('lblModalOrderCode').textContent    = data.purchase_order_code || 'N/A';
                document.getElementById('lblModalPurchaseCode').textContent = data.purchase_code || '';
                modalHiddenOrderId.value = data.id_purchase_order || '';

                document.getElementById('modal_supplier_invoice_number').value = data.supplier_invoice_number || '';
                document.getElementById('modal_supplier_invoice_date').value   = data.supplier_invoice_date || '';
                document.getElementById('modal_purchase_date').value           = data.purchase_date || '';
                document.getElementById('modal_status').value                  = data.status || 'draft';
                document.getElementById('modal_id_supplier').value             = data.id_supplier || '';
                document.getElementById('modal_id_branch').value               = data.id_branch || '';
                document.getElementById('modal_id_warehouse').value            = data.id_warehouse || '';
                document.getElementById('modal_currency').value                = data.currency || 'USD';
                document.getElementById('modal_notes').value                   = data.notes || '';

                renderizarDetallesModal(data.details, true);
            })
            .catch(err => {
                console.error(err);
                modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-rose-500">Error al cargar la compra. Intente nuevamente.</td></tr>';
            });
    }

    function closePurchaseModal() {
        purchaseModal.classList.add('hidden');
        purchaseModal.classList.remove('flex');
    }

    // Manejador del select de orden de compra
    if (modalOrderSelect) {
        modalOrderSelect.addEventListener('change', function () {
            const orderId = this.value;
            modalHiddenOrderId.value = orderId;
            if (!orderId) {
                modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Seleccione una orden de compra para cargar productos.</td></tr>';
                recalcularTotalesModal();
                return;
            }

            modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">Cargando productos de la orden...</td></tr>';

            fetch(`/purchases/order-data/${orderId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.id_supplier) document.getElementById('modal_id_supplier').value = data.id_supplier;
                    if (data.id_branch) document.getElementById('modal_id_branch').value = data.id_branch;
                    if (data.id_warehouse) document.getElementById('modal_id_warehouse').value = data.id_warehouse;
                    if (data.currency) document.getElementById('modal_currency').value = data.currency;

                    renderizarDetallesModal(data.details, false);
                })
                .catch(err => {
                    console.error('Error al cargar datos de orden:', err);
                    modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-rose-500">Error al cargar la orden de compra. Intente nuevamente.</td></tr>';
                });
        });
    }

    function renderizarDetallesModal(details, isEditing) {
        modalItemsBody.innerHTML = '';
        if (!details || details.length === 0) {
            modalItemsBody.innerHTML = '<tr><td colspan="9" class="py-8 text-center text-slate-400">La orden no tiene productos registrados.</td></tr>';
            recalcularTotalesModal();
            return;
        }

        details.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'modal-item-row hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.index = idx;

            const qtyVal = isEditing ? (d.quantity_received || d.quantity_ordered) : (d.pending_quantity > 0 ? d.pending_quantity : d.quantity_ordered);

            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <input type="hidden" name="details[${idx}][id_purchase_order_detail]" value="${d.id_purchase_order_detail || ''}">
                    <input type="hidden" name="details[${idx}][id_product]" value="${d.id_product}">
                    <input type="hidden" name="details[${idx}][id_unit]" value="${d.id_unit || ''}">
                    <input type="hidden" name="details[${idx}][quantity_ordered]" value="${d.quantity_ordered}">
                    <div class="font-bold text-slate-800 dark:text-slate-200">${d.product_name || 'Producto'}</div>
                    <div class="text-[11px] text-slate-400 font-mono">${d.unit_name || ''} ${d.product_code ? '• ' + d.product_code : ''}</div>
                    ${!isEditing && d.already_received > 0 ? `<div class="text-[10px] text-amber-600 font-semibold">Ya recibido: ${d.already_received}</div>` : ''}
                </td>
                <td class="py-2.5 px-2 text-center font-mono font-bold text-slate-600 dark:text-slate-400">
                    ${parseFloat(d.quantity_ordered).toFixed(2)}
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0.0001" name="details[${idx}][quantity_received]" value="${qtyVal}" required class="w-full px-2 py-1 text-center font-mono font-bold rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 m-qty-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0" name="details[${idx}][unit_price]" value="${d.unit_price}" required class="w-full px-2 py-1 text-right font-mono rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 m-price-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0" name="details[${idx}][discount]" value="${d.discount || 0}" class="w-full px-2 py-1 text-right font-mono rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 m-discount-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.01" min="0" max="100" name="details[${idx}][tax_rate]" value="${d.tax_rate || 0}" class="w-full px-2 py-1 text-center font-mono rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 m-tax-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-3 text-right font-mono m-row-subtotal">$0.00</td>
                <td class="py-2.5 px-3 text-right font-mono m-row-tax">$0.00</td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 m-row-total">$0.00</td>
            `;
            modalItemsBody.appendChild(tr);
        });

        vincularEventosModal();
        recalcularTotalesModal();
    }

    function vincularEventosModal() {
        document.querySelectorAll('.m-qty-input, .m-price-input, .m-discount-input, .m-tax-input').forEach(input => {
            input.removeEventListener('input', recalcularTotalesModal);
            input.addEventListener('input', recalcularTotalesModal);
        });
    }

    function recalcularTotalesModal() {
        let totalSubtotal = 0;
        let totalDiscount = 0;
        let totalTax      = 0;

        document.querySelectorAll('.modal-item-row').forEach(row => {
            const qty      = parseFloat(row.querySelector('.m-qty-input')?.value) || 0;
            const price    = parseFloat(row.querySelector('.m-price-input')?.value) || 0;
            const discount = parseFloat(row.querySelector('.m-discount-input')?.value) || 0;
            const taxRate  = parseFloat(row.querySelector('.m-tax-input')?.value) || 0;

            const lineSubtotal = qty * price;
            const base         = Math.max(0, lineSubtotal - discount);
            const lineTax      = base * (taxRate / 100);
            const lineTotal    = base + lineTax;

            totalSubtotal += lineSubtotal;
            totalDiscount += discount;
            totalTax      += lineTax;

            const tdSub = row.querySelector('.m-row-subtotal');
            const tdTax = row.querySelector('.m-row-tax');
            const tdTot = row.querySelector('.m-row-total');

            if (tdSub) tdSub.textContent = '$' + lineSubtotal.toFixed(2);
            if (tdTax) tdTax.textContent = '$' + lineTax.toFixed(2);
            if (tdTot) tdTot.textContent = '$' + lineTotal.toFixed(2);
        });

        const grandTotal = Math.max(0, totalSubtotal - totalDiscount) + totalTax;

        const elSub = document.getElementById('modalLblSubtotal');
        const elDis = document.getElementById('modalLblDiscount');
        const elTax = document.getElementById('modalLblTax');
        const elTot = document.getElementById('modalLblTotal');

        if (elSub) elSub.textContent = '$' + totalSubtotal.toFixed(2);
        if (elDis) elDis.textContent = '-$' + totalDiscount.toFixed(2);
        if (elTax) elTax.textContent = '$' + totalTax.toFixed(2);
        if (elTot) elTot.textContent = '$' + grandTotal.toFixed(2);
    }

    // Modal de Eliminación
    const deletePurchaseModal = document.getElementById('deletePurchaseModal');
    const deletePurchaseForm  = document.getElementById('deletePurchaseForm');
    const deletePurchaseTitle = document.getElementById('deletePurchaseTitle');

    function openDeletePurchaseModal(actionUrl, purchaseCode) {
        deletePurchaseTitle.textContent = `¿Eliminar Compra ${purchaseCode}?`;
        deletePurchaseForm.action       = actionUrl;
        deletePurchaseModal.classList.remove('hidden');
        deletePurchaseModal.classList.add('flex');
    }

    function closeDeletePurchaseModal() {
        deletePurchaseModal.classList.add('hidden');
        deletePurchaseModal.classList.remove('flex');
    }

    // Cerrar con Escape
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closePurchaseModal();
            closeDeletePurchaseModal();
        }
    });
</script>
@endsection

