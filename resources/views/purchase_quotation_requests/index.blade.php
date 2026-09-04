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
            <a href="{{ route('purchase-quotation-requests.create') }}" class="w-full md:w-auto bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold px-5 py-3 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-sm transform hover:-translate-y-0.5">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M12 4v16m8-8H4"/></svg>
                <span>Nueva Solicitud de Cotización</span>
            </a>
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
                                        <a href="{{ route('purchase-quotation-requests.create') }}" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-full bg-[#005e66] hover:bg-[#3cb0a4] text-white font-bold text-sm shadow-md transition-all">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                            <span>Nueva Solicitud de Cotización</span>
                                        </a>
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
@endsection
