@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Cotización #' . str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT))

@section('content')
<div class="w-full max-w-5xl mx-auto space-y-6 animate-fade-in duration-300">
    <!-- Encabezado con Acciones -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4 border-b border-slate-200 dark:border-slate-800 pb-5">
        <div>
            <div class="flex items-center gap-2">
                <a href="{{ route('purchase-quotation-requests.index') }}" class="text-xs font-bold text-slate-400 hover:text-[#005e66] transition-colors">Solicitudes de Cotización</a>
                <span class="text-slate-300 dark:text-slate-700">/</span>
                <span class="text-xs font-extrabold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Detalle</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                    Solicitud de Cotización #{{ str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT) }}
                </h1>
            </div>
            <p class="text-slate-400 text-xs mt-1">Registrada el {{ $quotationRequest->created_at->format('d/m/Y \a \l\a\s h:i A') }}</p>
        </div>
        <div class="flex items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="window.print()" class="flex-1 md:flex-none px-4 py-2.5 rounded-full border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-800 text-slate-700 dark:text-slate-300 font-bold text-xs transition-all flex items-center justify-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Imprimir</span>
            </button>
            <a href="{{ route('purchase-quotation-requests.index') }}" class="flex-1 md:flex-none px-5 py-2.5 rounded-full bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Grid de Tarjetas de Información -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Tarjeta de la Solicitud de Compra Origen -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    📋
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitud de Compra Origen</h2>
                    <span class="text-xs text-slate-400">Documento base (id_purchase_request)</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">ID Solicitud Compra (id_purchase_request):</span>
                    <span class="font-mono font-extrabold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-xs">
                        #{{ $quotationRequest->purchaseRequest->id_purchase_request ?? 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Código Solicitud:</span>
                    <span class="font-mono font-extrabold text-[#005e66] dark:text-teal-300 bg-teal-50 dark:bg-teal-950 px-2.5 py-0.5 rounded-lg border border-teal-200 dark:border-teal-800">
                        {{ $quotationRequest->purchaseRequest->purchase_request_code ?? 'N/A' }}
                    </span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Sucursal Destino:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->purchaseRequest->branch->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Bodega:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->purchaseRequest->warehouse->name ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Fecha Requerida:</span>
                    <span class="font-bold text-slate-700 dark:text-slate-300">
                        {{ $quotationRequest->purchaseRequest?->required_date ? $quotationRequest->purchaseRequest->required_date->format('d/m/Y') : 'N/A' }}
                    </span>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Cotización Recibida Asociada (id_purchase_quotation) -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    🔗
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Cotización Asociada</h2>
                    <span class="text-xs text-slate-400">Campo id_purchase_quotation</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">ID Cotización (id_purchase_quotation):</span>
                    <span class="font-mono text-sm font-extrabold text-slate-800 dark:text-white">
                        {{ $quotationRequest->id_purchase_quotation ? '#' . $quotationRequest->id_purchase_quotation : 'Ninguna (NULL)' }}
                    </span>
                </div>
                <p class="text-xs text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                    Este campo vinculará la cotización formal cuando sea procesada en el sistema.
                </p>
            </div>
        </div>
    </div>

    <!-- Tabla de Ítems Solicitados a Cotizar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">Detalles de la Solicitud de Cotización</h2>
                <p class="text-xs text-slate-400">Registros de la tabla purchase_quotation_request_details</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $details->count() }} producto(s)
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">ID Detalle Cot. Req.</th>
                        <th class="py-3.5 px-6">ID Detalle Sol. Compra</th>
                        <th class="py-3.5 px-6">Producto</th>
                        <th class="py-3.5 px-6">Unidad</th>
                        <th class="py-3.5 px-6 text-center">Cantidad (quantity)</th>
                        <th class="py-3.5 px-6 text-center">ID Detalle Cotización</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($details as $detail)
                        @php
                            $product = $detail->purchaseRequestDetail?->product;
                            $unit = $detail->purchaseRequestDetail?->unit;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-xs text-slate-400">
                                #{{ $detail->id_purchase_quotation_request_detail }}
                            </td>
                            <td class="py-4 px-6 font-mono text-xs font-bold text-slate-700 dark:text-slate-300">
                                #{{ $detail->id_purchase_request_detail }}
                            </td>
                            <td class="py-4 px-6">
                                <span class="font-extrabold text-slate-800 dark:text-white block">{{ $product->name ?? 'Producto no especificado' }}</span>
                                @if($product?->sku)
                                    <span class="text-xs font-mono text-slate-400">SKU: {{ $product->sku }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $unit->name ?? 'Unidad' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-mono text-base font-extrabold text-slate-800 dark:text-white">
                                    {{ number_format($detail->quantity, 2) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center font-mono text-xs font-bold text-slate-500">
                                {{ $detail->id_purchase_quotation_detail ? '#' . $detail->id_purchase_quotation_detail : 'NULL' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="py-8 text-center text-slate-400">
                                No se encontraron detalles para esta solicitud de cotización.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
@endsection
