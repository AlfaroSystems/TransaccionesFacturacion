@extends('layouts.app')
@section('title', 'Retaceos y Costeo de Importaciones')

@section('content')
@php
    $statusClasses = [
        'draft'      => 'bg-slate-100 text-slate-700 border-slate-300 dark:bg-slate-800 dark:text-slate-300 dark:border-slate-600',
        'calculated' => 'bg-sky-100 text-sky-800 border-sky-300 dark:bg-sky-950 dark:text-sky-300 dark:border-sky-800',
        'applied'    => 'bg-emerald-100 text-emerald-800 border-emerald-300 dark:bg-emerald-950 dark:text-emerald-300 dark:border-emerald-800',
        'cancelled'  => 'bg-rose-100 text-rose-800 border-rose-300 dark:bg-rose-950 dark:text-rose-300 dark:border-rose-800',
    ];
    $statusNames = [
        'draft'      => 'Borrador',
        'calculated' => 'Liquidado / Calculado',
        'applied'    => 'Aplicado a Inventario',
        'cancelled'  => 'Cancelado',
    ];
@endphp

<div class="w-full space-y-6 animate-fade-in duration-300">
    <!-- Encabezado -->
    <div class="flex flex-col md:flex-row md:items-center md:justify-between gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-lg">Compras</span>
                <span class="text-slate-400">•</span>
                <span class="text-xs font-semibold text-slate-500">Módulo de Retaceos e Importaciones</span>
            </div>
            <h1 class="text-2xl md:text-3xl font-extrabold text-[#005e66] tracking-tight mt-1">
                Retaceos de Importación
            </h1>
            <p class="text-sm text-slate-500 mt-0.5">
                Liquidación y prorrateo de costos de importación (FOB, flete internacional, seguro, gastos aduanales y DAI).
            </p>
        </div>
        @can('retaceos.crear')
            <button type="button" onclick="openCreateRetaceoModal()" class="inline-flex items-center gap-2 px-5 py-2.5 bg-[#005e66] hover:bg-[#00474f] text-white font-bold rounded-xl shadow-md transition-all text-sm transform hover:-translate-y-0.5 cursor-pointer">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/>
                </svg>
                <span>Nuevo Retaceo</span>
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
                <span>Revise los errores detectados en el formulario:</span>
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
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Total Retaceos</p>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">{{ $totalCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-teal-50 dark:bg-teal-900/40 text-[#005e66] dark:text-teal-300 flex items-center justify-center text-xl font-bold">
                📊
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
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Liquidados</p>
                <p class="text-2xl font-extrabold text-slate-800 dark:text-slate-100 mt-1">{{ $calculatedCount }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-emerald-50 dark:bg-emerald-900/40 text-emerald-600 dark:text-emerald-300 flex items-center justify-center text-xl font-bold">
                ✓
            </div>
        </div>
        <div class="bg-white dark:bg-slate-800/80 rounded-2xl p-5 border border-slate-100 dark:border-slate-700 shadow-sm flex items-center justify-between">
            <div>
                <p class="text-xs font-bold text-slate-400 uppercase tracking-wider">Costo Total Liquidado</p>
                <p class="text-2xl font-extrabold text-[#005e66] dark:text-teal-400 mt-1">${{ number_format($totalCostAmount, 2) }}</p>
            </div>
            <div class="w-12 h-12 rounded-xl bg-indigo-50 dark:bg-indigo-900/40 text-indigo-600 dark:text-indigo-300 flex items-center justify-center text-xl font-bold">
                🚢
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 p-5 shadow-sm">
        <form method="GET" action="{{ route('retaceos.index') }}" class="grid grid-cols-1 md:grid-cols-5 gap-4">
            <div class="md:col-span-2">
                <label class="block text-xs font-bold text-slate-400 uppercase mb-1">Buscar</label>
                <input type="text" name="search" value="{{ request('search') }}" placeholder="Código, factura exterior, póliza DM..." class="w-full px-4 py-2 rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
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
                    <option value="calculated" {{ request('status') === 'calculated' ? 'selected' : '' }}>Liquidado</option>
                    <option value="applied" {{ request('status') === 'applied' ? 'selected' : '' }}>Aplicado a Inventario</option>
                    <option value="cancelled" {{ request('status') === 'cancelled' ? 'selected' : '' }}>Cancelado</option>
                </select>
            </div>
            <div class="flex items-end gap-2">
                <button type="submit" class="w-full bg-[#005e66] hover:bg-[#00474f] text-white font-bold py-2 px-4 rounded-xl text-sm transition-all shadow-sm">
                    Filtrar
                </button>
                @if(request()->hasAny(['search', 'id_supplier', 'status', 'date_from', 'date_to']))
                    <a href="{{ route('retaceos.index') }}" class="p-2 bg-slate-100 dark:bg-slate-700 hover:bg-slate-200 text-slate-600 dark:text-slate-300 rounded-xl" title="Limpiar filtros">
                        ✕
                    </a>
                @endif
            </div>
        </form>
    </div>

    <!-- Tabla de Retaceos -->
    <div class="bg-white dark:bg-slate-800/80 rounded-2xl border border-slate-100 dark:border-slate-700 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="bg-slate-50 dark:bg-slate-900/50 text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider border-b border-slate-100 dark:border-slate-700">
                    <tr>
                        <th class="py-4 px-6">Código / Liquidación</th>
                        <th class="py-4 px-6">Factura de Compra</th>
                        <th class="py-4 px-6">Proveedor / Origen</th>
                        <th class="py-4 px-6">Póliza / DM Aduana</th>
                        <th class="py-4 px-6 text-right">Total FOB</th>
                        <th class="py-4 px-6 text-right">Costo Liquidado</th>
                        <th class="py-4 px-6 text-center">Estado</th>
                        <th class="py-4 px-6 text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-700/60">
                    @forelse($retaceos as $retaceo)
                        <tr class="hover:bg-slate-50/80 dark:hover:bg-slate-700/30 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-extrabold text-[#005e66] dark:text-teal-400 block font-mono">
                                    {{ $retaceo->retaceo_code }}
                                </span>
                                <span class="text-xs text-slate-400">
                                    {{ $retaceo->retaceo_date ? $retaceo->retaceo_date->format('d/m/Y') : '-' }}
                                </span>
                            </td>
                            <td class="py-4 px-6">
                                @if($retaceo->purchase)
                                    <a href="{{ route('purchases.show', $retaceo->purchase->id_purchase) }}" class="font-semibold text-sky-600 dark:text-sky-400 hover:underline font-mono text-xs">
                                        {{ $retaceo->purchase->purchase_code }}
                                    </a>
                                    @if($retaceo->purchase->supplier_invoice_number)
                                        <div class="text-[11px] text-slate-400">Fac: {{ $retaceo->purchase->supplier_invoice_number }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400 text-xs">N/A</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-bold text-slate-800 dark:text-slate-200 block">
                                    {{ $retaceo->supplier->name ?? 'Proveedor no asignado' }}
                                </span>
                                @if($retaceo->origin_country)
                                    <span class="text-xs text-slate-400">Origen: {{ $retaceo->origin_country }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-600 dark:text-slate-400">
                                @if($retaceo->import_policy_number)
                                    <div class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $retaceo->import_policy_number }}</div>
                                    @if($retaceo->import_policy_date)
                                        <div class="text-[11px] text-slate-400">{{ $retaceo->import_policy_date->format('d/m/Y') }}</div>
                                    @endif
                                @else
                                    <span class="text-slate-400">Sin póliza</span>
                                @endif
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-bold text-slate-700 dark:text-slate-300 font-mono text-sm">
                                    ${{ number_format($retaceo->total_fob, 2) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-right">
                                <span class="font-extrabold text-[#005e66] dark:text-teal-400 font-mono text-sm block">
                                    ${{ number_format($retaceo->total_cost, 2) }}
                                </span>
                                <span class="text-[11px] text-slate-400">
                                    +${{ number_format($retaceo->total_freight + $retaceo->total_expenses + $retaceo->total_dai, 2) }} gastos
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-extrabold border {{ $statusClasses[$retaceo->status] ?? 'bg-slate-100 text-slate-700' }}">
                                    ● {{ $statusNames[$retaceo->status] ?? ucfirst($retaceo->status) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <div class="flex items-center justify-center gap-1.5">
                                    @can('retaceos.ver')
                                        <a href="{{ route('retaceos.show', $retaceo->id_retaceo) }}" class="p-2 rounded-xl hover:bg-teal-50 dark:hover:bg-teal-950/50 text-[#005e66] dark:text-teal-300 transition-colors" title="Ver Detalle Liquidado">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                        </a>
                                    @endcan
                                    @if($retaceo->status === 'draft')
                                        @can('retaceos.editar')
                                            <button type="button" onclick="openEditRetaceoModal({{ $retaceo->id_retaceo }})" class="p-2 rounded-xl hover:bg-amber-50 dark:hover:bg-amber-950/50 text-amber-600 dark:text-amber-400 transition-colors cursor-pointer" title="Editar Retaceo (Modal)">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                        @endcan
                                        @can('retaceos.eliminar')
                                            <button type="button" onclick="openDeleteRetaceoModal('{{ route('retaceos.destroy', $retaceo->id_retaceo) }}', '{{ $retaceo->retaceo_code }}')" class="p-2 rounded-xl hover:bg-rose-50 dark:hover:bg-rose-950/50 text-rose-600 dark:text-rose-400 transition-colors cursor-pointer" title="Eliminar Retaceo">
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
                                <div class="text-4xl mb-2">🚢</div>
                                <p class="font-bold">No se encontraron retaceos de importación registrados.</p>
                                <p class="text-xs mt-1">Haga clic en "Nuevo Retaceo" para liquidar y prorratear costos de una compra.</p>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if($retaceos->hasPages())
            <div class="p-4 border-t border-slate-100 dark:border-slate-700">
                {{ $retaceos->links() }}
            </div>
        @endif
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE CREACIÓN / EDICIÓN DE RETACEO     -->
<!-- ========================================== -->
<div id="retaceoModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-3 sm:p-4 overflow-y-auto">
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-5xl w-full max-h-[92vh] flex flex-col overflow-hidden my-auto transform transition-all animate-scale-up">
        <!-- Modal Header -->
        <div class="px-6 py-4 border-b border-slate-100 dark:border-slate-700 flex items-center justify-between bg-slate-50/70 dark:bg-slate-800/80">
            <div>
                <span id="retaceoModalBadge" class="px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-md">Nuevo Retaceo</span>
                <h2 id="retaceoModalTitle" class="text-lg font-extrabold text-slate-800 dark:text-slate-100 mt-0.5">
                    Calcular y Liquidar Retaceo de Importación
                </h2>
                <p id="retaceoModalSubtitle" class="text-xs text-slate-400">
                    Seleccione la compra exterior e ingrese los gastos para prorratear los costos unitarios.
                </p>
            </div>
            <button type="button" onclick="closeRetaceoModal()" class="w-8 h-8 rounded-full bg-slate-100 dark:bg-slate-700 text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 hover:bg-slate-200 flex items-center justify-center transition-colors">
                ✕
            </button>
        </div>

        <!-- Modal Form -->
        <form id="retaceoModalForm" method="POST" action="{{ route('retaceos.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="_method" id="retaceoModalMethod" value="POST">
            <input type="hidden" name="id_purchase" id="modalHiddenPurchaseId" value="">

            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Selector de Compra (Modo Crear) -->
                <div id="modalPurchaseSelectContainer" class="bg-indigo-50/80 dark:bg-slate-900/60 border border-indigo-200 dark:border-slate-700 rounded-2xl p-4 space-y-2">
                    <div class="flex items-center justify-between">
                        <label for="modalPurchaseSelect" class="text-xs font-extrabold text-indigo-900 dark:text-indigo-300 uppercase tracking-wider flex items-center gap-1.5">
                            <span>🧾 Factura de Compra Exterior Asociada *</span>
                        </label>
                        <span class="text-[11px] text-indigo-600 dark:text-indigo-400">Compras registradas no canceladas</span>
                    </div>
                    <select id="modalPurchaseSelect" class="w-full px-3.5 py-2 rounded-xl border border-indigo-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-semibold text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66] outline-none">
                        <option value="">-- Seleccione una compra exterior --</option>
                        @foreach($purchasesForModal as $pur)
                            <option value="{{ $pur->id_purchase }}">
                                {{ $pur->purchase_code }} {{ $pur->supplier_invoice_number ? '(Fac: '.$pur->supplier_invoice_number.')' : '' }} - {{ $pur->supplier->name ?? 'Sin Proveedor' }} (${{ number_format($pur->total, 2) }})
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Resumen de Compra (Modo Editar) -->
                <div id="modalPurchaseInfoBox" class="hidden bg-slate-100 dark:bg-slate-900/50 rounded-xl p-3 text-xs font-semibold text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 flex items-center justify-between">
                    <div>
                        Factura de Compra: <strong id="lblModalPurchaseCodeRef" class="font-mono text-sm text-[#005e66] dark:text-teal-400"></strong>
                    </div>
                    <div>
                        Código de Retaceo: <strong id="lblModalRetaceoCode" class="font-mono text-sm text-slate-800 dark:text-slate-100"></strong>
                    </div>
                </div>

                <!-- Cabecera de Importación -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Proveedor *</label>
                        <select name="id_supplier" id="modal_r_id_supplier" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="">-- Proveedor --</option>
                            @foreach($suppliers as $sup)
                                <option value="{{ $sup->id_supplier }}">{{ $sup->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">País de Origen</label>
                        <input type="text" name="origin_country" id="modal_r_origin_country" placeholder="Ej: Estados Unidos, China" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Liquidación *</label>
                        <input type="datetime-local" name="retaceo_date" id="modal_r_retaceo_date" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Estado *</label>
                        <select name="status" id="modal_r_status" required class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                            <option value="calculated">Liquidado / Calculado</option>
                            <option value="draft">Borrador</option>
                            <option value="applied">Aplicado a Inventario</option>
                        </select>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Factura Exterior</label>
                        <input type="text" name="import_invoice_number" id="modal_r_import_invoice_number" placeholder="Ej: INV-2026-99" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Factura Exterior</label>
                        <input type="date" name="import_invoice_date" id="modal_r_import_invoice_date" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">No. Póliza / DM Aduana</label>
                        <input type="text" name="import_policy_number" id="modal_r_import_policy_number" placeholder="Ej: POL-2026-4482" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                    <div>
                        <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Fecha Póliza Aduana</label>
                        <input type="date" name="import_policy_date" id="modal_r_import_policy_date" class="w-full px-3 py-2 text-sm font-semibold rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]">
                    </div>
                </div>

                <!-- Gastos Globales de Importación -->
                <div class="bg-amber-50/70 dark:bg-slate-900/60 p-4 rounded-2xl border border-amber-200 dark:border-slate-700 space-y-2">
                    <div class="text-xs font-extrabold text-amber-900 dark:text-amber-300 uppercase tracking-wider flex items-center gap-1.5">
                        <span>⚓ Gastos Generales de Importación a Prorratear</span>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Total Flete Internacional ($) *</label>
                            <input type="number" step="0.0001" min="0" name="total_freight" id="modal_r_total_freight" value="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-bold font-mono text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#005e66]">
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-600 dark:text-slate-300 mb-1">Total Otros Gastos Aduanales / Seguro ($) *</label>
                            <input type="number" step="0.0001" min="0" name="total_expenses" id="modal_r_total_expenses" value="0.00" class="w-full px-3 py-2 rounded-xl border border-slate-300 dark:border-slate-600 bg-white dark:bg-slate-700 text-sm font-bold font-mono text-slate-800 dark:text-slate-100 focus:ring-2 focus:ring-[#005e66]">
                        </div>
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-bold text-slate-500 uppercase mb-1">Notas u Observaciones</label>
                    <textarea name="notes" id="modal_r_notes" rows="2" placeholder="Observaciones de la liquidación o agente aduanal..." class="w-full px-3 py-2 text-sm rounded-xl border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 text-slate-800 dark:text-slate-200 focus:ring-2 focus:ring-[#005e66]"></textarea>
                </div>

                <!-- Tabla de Productos y Prorrateo -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-2xl overflow-hidden">
                    <div class="bg-slate-50 dark:bg-slate-900/60 px-4 py-2.5 border-b border-slate-200 dark:border-slate-700 flex items-center justify-between">
                        <span class="text-xs font-extrabold uppercase text-slate-600 dark:text-slate-300">Distribución y Prorrateo de Costos</span>
                        <span class="text-[11px] text-slate-400">Prorrateo ponderado según el valor FOB</span>
                    </div>
                    <div class="overflow-x-auto max-h-60">
                        <table class="w-full text-left text-xs" id="modalRetaceoTable">
                            <thead class="bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase font-bold tracking-wider border-b border-slate-200 dark:border-slate-700 sticky top-0 z-10">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-2 text-center w-20">Cantidad</th>
                                    <th class="py-2.5 px-2 w-24">FOB ($) *</th>
                                    <th class="py-2.5 px-2 text-right w-20">Flete Asig.</th>
                                    <th class="py-2.5 px-2 text-right w-20">Gastos Asig.</th>
                                    <th class="py-2.5 px-2 w-20">DAI ($)</th>
                                    <th class="py-2.5 px-3 text-right w-24">Costo Total</th>
                                    <th class="py-2.5 px-3 text-right w-24 text-teal-600 dark:text-teal-400">Costo Unit.</th>
                                </tr>
                            </thead>
                            <tbody id="modalRetaceoBody" class="divide-y divide-slate-100 dark:divide-slate-700/60">
                                <tr>
                                    <td colspan="8" class="py-8 text-center text-slate-400">
                                        Seleccione una compra exterior en la parte superior para cargar los productos.
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                    <div class="bg-slate-50 dark:bg-slate-900/60 p-4 border-t border-slate-200 dark:border-slate-700 flex justify-end">
                        <div class="w-full sm:w-72 space-y-1.5 text-xs font-semibold">
                            <div class="flex justify-between text-slate-500">
                                <span>Total FOB:</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblFob">$0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Total Flete:</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblFreight">$0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Total Gastos:</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblExpenses">$0.00</span>
                            </div>
                            <div class="flex justify-between text-slate-500">
                                <span>Total DAI / Aranceles:</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200" id="modalLblDai">$0.00</span>
                            </div>
                            <div class="flex justify-between text-sm font-extrabold text-slate-800 dark:text-slate-100 pt-1.5 border-t border-slate-200 dark:border-slate-700">
                                <span>Costo Total Liquidado:</span>
                                <span class="font-mono text-[#005e66] dark:text-teal-400 text-base" id="modalLblGrandTotal">$0.00</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Modal Footer -->
            <div class="px-6 py-4 bg-slate-50 dark:bg-slate-800/80 border-t border-slate-100 dark:border-slate-700 flex items-center justify-end gap-3">
                <button type="button" onclick="closeRetaceoModal()" class="px-5 py-2 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
                    Cancelar
                </button>
                <button type="submit" id="btnSubmitRetaceoModal" class="px-6 py-2 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white font-bold text-xs shadow-md transition-all">
                    Guardar Retaceo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- ========================================== -->
<!-- MODAL DE CONFIRMACIÓN DE ELIMINACIÓN       -->
<!-- ========================================== -->
<div id="deleteRetaceoModal" class="fixed inset-0 z-50 bg-slate-900/60 backdrop-blur-sm hidden items-center justify-center p-4">
    <div class="bg-white dark:bg-slate-800 rounded-3xl border border-slate-100 dark:border-slate-700 shadow-2xl max-w-md w-full p-6 text-center animate-scale-up">
        <div class="w-14 h-14 mx-auto rounded-2xl bg-rose-100 dark:bg-rose-900/40 text-rose-600 dark:text-rose-300 flex items-center justify-center text-2xl mb-4">
            ⚠️
        </div>
        <h3 class="text-lg font-extrabold text-slate-800 dark:text-slate-100 mb-1" id="deleteRetaceoTitle">
            ¿Eliminar Retaceo?
        </h3>
        <p class="text-xs text-slate-500 dark:text-slate-400 mb-6" id="deleteRetaceoDesc">
            Esta acción eliminará de forma permanente el retaceo en borrador y todos sus cálculos de prorrateo.
        </p>
        <form id="deleteRetaceoForm" method="POST" action="">
            @csrf
            @method('DELETE')
            <div class="flex items-center justify-center gap-3">
                <button type="button" onclick="closeDeleteRetaceoModal()" class="px-5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-600 text-slate-700 dark:text-slate-300 font-bold text-xs hover:bg-slate-100 dark:hover:bg-slate-700 transition-colors">
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
    // Referencias del modal de retaceo
    const retaceoModal               = document.getElementById('retaceoModal');
    const retaceoModalForm           = document.getElementById('retaceoModalForm');
    const retaceoModalBadge          = document.getElementById('retaceoModalBadge');
    const retaceoModalTitle          = document.getElementById('retaceoModalTitle');
    const retaceoModalSubtitle       = document.getElementById('retaceoModalSubtitle');
    const retaceoModalMethod         = document.getElementById('retaceoModalMethod');
    const btnSubmitRetaceoModal      = document.getElementById('btnSubmitRetaceoModal');
    const modalPurchaseSelectContainer = document.getElementById('modalPurchaseSelectContainer');
    const modalPurchaseInfoBox       = document.getElementById('modalPurchaseInfoBox');
    const modalPurchaseSelect        = document.getElementById('modalPurchaseSelect');
    const modalHiddenPurchaseId      = document.getElementById('modalHiddenPurchaseId');
    const modalRetaceoBody           = document.getElementById('modalRetaceoBody');
    const inputFreight               = document.getElementById('modal_r_total_freight');
    const inputExpenses              = document.getElementById('modal_r_total_expenses');

    if (inputFreight) inputFreight.addEventListener('input', recalcularProrrateoModal);
    if (inputExpenses) inputExpenses.addEventListener('input', recalcularProrrateoModal);

    // Apertura en modo Creación
    function openCreateRetaceoModal() {
        retaceoModalBadge.textContent    = 'Nuevo Retaceo';
        retaceoModalBadge.className      = 'px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] rounded-md';
        retaceoModalTitle.textContent    = 'Calcular y Liquidar Retaceo de Importación';
        retaceoModalSubtitle.textContent = 'Seleccione una compra exterior e ingrese los gastos para prorratear los costos unitarios.';
        btnSubmitRetaceoModal.textContent= 'Guardar Retaceo';

        retaceoModalForm.action = "{{ route('retaceos.store') }}";
        retaceoModalMethod.value = "POST";

        modalPurchaseSelectContainer.classList.remove('hidden');
        modalPurchaseInfoBox.classList.add('hidden');
        modalPurchaseSelect.value = '';
        modalHiddenPurchaseId.value = '';

        // Reset cabecera
        document.getElementById('modal_r_id_supplier').value           = '';
        document.getElementById('modal_r_origin_country').value        = '';
        document.getElementById('modal_r_retaceo_date').value          = new Date().toISOString().slice(0, 16);
        document.getElementById('modal_r_status').value                = 'calculated';
        document.getElementById('modal_r_import_invoice_number').value = '';
        document.getElementById('modal_r_import_invoice_date').value   = '';
        document.getElementById('modal_r_import_policy_number').value  = '';
        document.getElementById('modal_r_import_policy_date').value    = '';
        document.getElementById('modal_r_total_freight').value         = '0.00';
        document.getElementById('modal_r_total_expenses').value        = '0.00';
        document.getElementById('modal_r_notes').value                 = '';

        modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">Seleccione una compra exterior en la parte superior para cargar los productos.</td></tr>';
        recalcularProrrateoModal();

        retaceoModal.classList.remove('hidden');
        retaceoModal.classList.add('flex');
    }

    // Apertura en modo Edición
    function openEditRetaceoModal(retaceoId) {
        retaceoModalBadge.textContent    = 'Modo Edición';
        retaceoModalBadge.className      = 'px-2.5 py-0.5 text-[11px] font-extrabold uppercase tracking-wider bg-amber-100 text-amber-800 rounded-md';
        retaceoModalTitle.textContent    = 'Editar Retaceo de Importación';
        retaceoModalSubtitle.textContent = 'Modifique los fletes, gastos o importes aduanales del retaceo en borrador.';
        btnSubmitRetaceoModal.textContent= 'Actualizar Retaceo';

        retaceoModalForm.action = `/retaceos/${retaceoId}`;
        retaceoModalMethod.value = "PUT";

        modalPurchaseSelectContainer.classList.add('hidden');
        modalPurchaseInfoBox.classList.remove('hidden');

        modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">Cargando datos del retaceo...</td></tr>';
        retaceoModal.classList.remove('hidden');
        retaceoModal.classList.add('flex');

        fetch(`/retaceos/${retaceoId}/edit-data`)
            .then(res => {
                if (!res.ok) throw new Error('Error al consultar datos');
                return res.json();
            })
            .then(data => {
                document.getElementById('lblModalPurchaseCodeRef').textContent = data.purchase_code || 'N/A';
                document.getElementById('lblModalRetaceoCode').textContent     = data.retaceo_code || '';
                modalHiddenPurchaseId.value = data.id_purchase || '';

                document.getElementById('modal_r_id_supplier').value           = data.id_supplier || '';
                document.getElementById('modal_r_origin_country').value        = data.origin_country || '';
                document.getElementById('modal_r_retaceo_date').value          = data.retaceo_date || '';
                document.getElementById('modal_r_status').value                = data.status || 'draft';
                document.getElementById('modal_r_import_invoice_number').value = data.import_invoice_number || '';
                document.getElementById('modal_r_import_invoice_date').value   = data.import_invoice_date || '';
                document.getElementById('modal_r_import_policy_number').value  = data.import_policy_number || '';
                document.getElementById('modal_r_import_policy_date').value    = data.import_policy_date || '';
                document.getElementById('modal_r_total_freight').value         = data.total_freight || 0;
                document.getElementById('modal_r_total_expenses').value        = data.total_expenses || 0;
                document.getElementById('modal_r_notes').value                 = data.notes || '';

                renderizarDetallesRetaceoModal(data.details);
            })
            .catch(err => {
                console.error(err);
                modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-rose-500">Error al cargar el retaceo. Intente nuevamente.</td></tr>';
            });
    }

    function closeRetaceoModal() {
        retaceoModal.classList.add('hidden');
        retaceoModal.classList.remove('flex');
    }

    // Manejador del select de compra exterior
    if (modalPurchaseSelect) {
        modalPurchaseSelect.addEventListener('change', function () {
            const purchaseId = this.value;
            modalHiddenPurchaseId.value = purchaseId;
            if (!purchaseId) {
                modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">Seleccione una compra exterior para cargar productos.</td></tr>';
                recalcularProrrateoModal();
                return;
            }

            modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">Cargando productos de la compra...</td></tr>';

            fetch(`/retaceos/purchase-data/${purchaseId}`)
                .then(res => res.json())
                .then(data => {
                    if (data.id_supplier) document.getElementById('modal_r_id_supplier').value = data.id_supplier;
                    if (data.origin_country) document.getElementById('modal_r_origin_country').value = data.origin_country;
                    if (data.supplier_invoice_number) document.getElementById('modal_r_import_invoice_number').value = data.supplier_invoice_number;
                    if (data.supplier_invoice_date) document.getElementById('modal_r_import_invoice_date').value = data.supplier_invoice_date;

                    renderizarDetallesRetaceoModal(data.details);
                })
                .catch(err => {
                    console.error('Error al cargar datos de compra:', err);
                    modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-rose-500">Error al cargar productos de la compra.</td></tr>';
                });
        });
    }

    function renderizarDetallesRetaceoModal(details) {
        modalRetaceoBody.innerHTML = '';
        if (!details || details.length === 0) {
            modalRetaceoBody.innerHTML = '<tr><td colspan="8" class="py-8 text-center text-slate-400">La compra no tiene productos registrados.</td></tr>';
            recalcularProrrateoModal();
            return;
        }

        details.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.className = 'modal-retaceo-row hover:bg-slate-50/60 dark:hover:bg-slate-700/30 transition-colors';
            tr.dataset.index = idx;

            tr.innerHTML = `
                <td class="py-2.5 px-3">
                    <input type="hidden" name="details[${idx}][id_purchase_detail]" value="${d.id_purchase_detail || ''}">
                    <input type="hidden" name="details[${idx}][id_product]" value="${d.id_product}">
                    <div class="font-bold text-slate-800 dark:text-slate-200">${d.product_name || 'Producto'}</div>
                    <div class="text-[11px] text-slate-400 font-mono">${d.unit_name || ''} ${d.product_code ? '• ' + d.product_code : ''}</div>
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0.0001" name="details[${idx}][quantity]" value="${d.quantity}" required class="w-full px-2 py-1 text-center font-mono font-bold rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 mr-qty-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0" name="details[${idx}][cost_fob]" value="${d.cost_fob}" required class="w-full px-2 py-1 text-right font-mono font-bold rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 mr-fob-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-2 text-right font-mono text-slate-500 dark:text-slate-400 mr-row-freight">$0.00</td>
                <td class="py-2.5 px-2 text-right font-mono text-slate-500 dark:text-slate-400 mr-row-expense">$0.00</td>
                <td class="py-2.5 px-2">
                    <input type="number" step="0.0001" min="0" name="details[${idx}][dai_amount]" value="${d.dai_amount || 0}" class="w-full px-2 py-1 text-right font-mono rounded-lg border border-slate-200 dark:border-slate-600 bg-white dark:bg-slate-700 mr-dai-input focus:ring-1 focus:ring-[#005e66]">
                </td>
                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-800 dark:text-slate-100 mr-row-total">$0.00</td>
                <td class="py-2.5 px-3 text-right font-mono font-extrabold text-[#005e66] dark:text-teal-400 mr-row-unit">$0.00</td>
            `;
            modalRetaceoBody.appendChild(tr);
        });

        vincularEventosRetaceoModal();
        recalcularProrrateoModal();
    }

    function vincularEventosRetaceoModal() {
        document.querySelectorAll('.mr-qty-input, .mr-fob-input, .mr-dai-input').forEach(input => {
            input.removeEventListener('input', recalcularProrrateoModal);
            input.addEventListener('input', recalcularProrrateoModal);
        });
    }

    function recalcularProrrateoModal() {
        const totalFreight  = parseFloat(document.getElementById('modal_r_total_freight')?.value) || 0;
        const totalExpenses = parseFloat(document.getElementById('modal_r_total_expenses')?.value) || 0;

        const rows = document.querySelectorAll('.modal-retaceo-row');
        let totalFob = 0;
        rows.forEach(r => {
            const fob = parseFloat(r.querySelector('.mr-fob-input')?.value) || 0;
            totalFob += fob;
        });

        let totalDai = 0;
        const count = rows.length;

        rows.forEach(r => {
            const qty  = Math.max(0.0001, parseFloat(r.querySelector('.mr-qty-input')?.value) || 1);
            const fob  = parseFloat(r.querySelector('.mr-fob-input')?.value) || 0;
            const dai  = parseFloat(r.querySelector('.mr-dai-input')?.value) || 0;

            const ratio = (totalFob > 0) ? (fob / totalFob) : (count > 0 ? (1 / count) : 0);

            const freightAmount = totalFreight * ratio;
            const expenseAmount = totalExpenses * ratio;
            const lineTotalCost = fob + freightAmount + expenseAmount + dai;
            const unitCost      = lineTotalCost / qty;

            totalDai += dai;

            const tdFreight = r.querySelector('.mr-row-freight');
            const tdExpense = r.querySelector('.mr-row-expense');
            const tdTotal   = r.querySelector('.mr-row-total');
            const tdUnit    = r.querySelector('.mr-row-unit');

            if (tdFreight) tdFreight.textContent = '$' + freightAmount.toFixed(2);
            if (tdExpense) tdExpense.textContent = '$' + expenseAmount.toFixed(2);
            if (tdTotal) tdTotal.textContent     = '$' + lineTotalCost.toFixed(2);
            if (tdUnit) tdUnit.textContent       = '$' + unitCost.toFixed(2);
        });

        const grandTotal = totalFob + totalFreight + totalExpenses + totalDai;

        const elFob   = document.getElementById('modalLblFob');
        const elFre   = document.getElementById('modalLblFreight');
        const elExp   = document.getElementById('modalLblExpenses');
        const elDai   = document.getElementById('modalLblDai');
        const elGrand = document.getElementById('modalLblGrandTotal');

        if (elFob) elFob.textContent     = '$' + totalFob.toFixed(2);
        if (elFre) elFre.textContent     = '$' + totalFreight.toFixed(2);
        if (elExp) elExp.textContent     = '$' + totalExpenses.toFixed(2);
        if (elDai) elDai.textContent     = '$' + totalDai.toFixed(2);
        if (elGrand) elGrand.textContent = '$' + grandTotal.toFixed(2);
    }

    // Modal de Eliminación de Retaceo
    const deleteRetaceoModal = document.getElementById('deleteRetaceoModal');
    const deleteRetaceoForm  = document.getElementById('deleteRetaceoForm');
    const deleteRetaceoTitle = document.getElementById('deleteRetaceoTitle');

    function openDeleteRetaceoModal(actionUrl, retaceoCode) {
        deleteRetaceoTitle.textContent = `¿Eliminar Retaceo ${retaceoCode}?`;
        deleteRetaceoForm.action       = actionUrl;
        deleteRetaceoModal.classList.remove('hidden');
        deleteRetaceoModal.classList.add('flex');
    }

    function closeDeleteRetaceoModal() {
        deleteRetaceoModal.classList.add('hidden');
        deleteRetaceoModal.classList.remove('flex');
    }

    // Cerrar con Escape
    window.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            closeRetaceoModal();
            closeDeleteRetaceoModal();
        }
    });
</script>
@endsection
