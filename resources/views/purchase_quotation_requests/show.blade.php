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
                <span class="text-xs font-extrabold text-[#005e66] dark:text-teal-400 uppercase tracking-wider">Detalle Convocatoria</span>
            </div>
            <div class="flex items-center gap-3 mt-1">
                <h1 class="text-3xl font-extrabold text-slate-800 dark:text-white tracking-tight">
                    Invitación #{{ str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT) }}
                </h1>
                @php $badge = $quotationRequest->status_badge; @endphp
                <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-extrabold border {{ $badge['class'] }}">
                    {{ $badge['label'] }}
                </span>
            </div>
            <p class="text-slate-400 text-xs mt-1">Emitida el {{ $quotationRequest->created_at->format('d/m/Y \a \l\a\s h:i A') }}</p>
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
        <!-- Tarjeta del Proveedor Convocado -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    🏢
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Proveedor Convocado</h2>
                    <span class="text-xs text-slate-400">Información del destinatario de la cotización</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Nombre:</span>
                    <span class="font-extrabold text-slate-800 dark:text-white">{{ $quotationRequest->supplier->name ?? 'N/A' }}</span>
                </div>
                @if($quotationRequest->supplier?->code)
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">Código Proveedor:</span>
                        <span class="font-mono text-xs font-bold text-[#005e66] dark:text-teal-400 bg-teal-50 dark:bg-teal-950 px-2 py-0.5 rounded">{{ $quotationRequest->supplier->code }}</span>
                    </div>
                @endif
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Email:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->supplier->email ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Teléfono:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->supplier->phone ?? 'N/A' }}</span>
                </div>
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">País:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->supplier->country ?? 'N/A' }}</span>
                </div>
                @if($quotationRequest->supplier?->address)
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <span class="text-slate-400 block font-bold uppercase mb-0.5">Dirección:</span>
                        <span class="text-slate-600 dark:text-slate-400">{{ $quotationRequest->supplier->address }}</span>
                    </div>
                @endif
            </div>
        </div>

        <!-- Tarjeta de la Solicitud de Compra de Origen -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-blue-50 dark:bg-blue-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    📋
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitud de Compra Origen</h2>
                    <span class="text-xs text-slate-400">Documento base que originó este requerimiento</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
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
                <div class="flex justify-between items-center">
                    <span class="text-xs font-bold text-slate-400 uppercase">Solicitado por:</span>
                    <span class="font-medium text-slate-700 dark:text-slate-300">{{ $quotationRequest->purchaseRequest->user->name ?? 'N/A' }}</span>
                </div>
                @if($quotationRequest->purchaseRequest?->justification)
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 text-xs">
                        <span class="text-slate-400 block font-bold uppercase mb-0.5">Justificación:</span>
                        <span class="text-slate-600 dark:text-slate-400 italic">{{ $quotationRequest->purchaseRequest->justification }}</span>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla de Ítems Solicitados a Cotizar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">Ítems Solicitados al Proveedor</h2>
                <p class="text-xs text-slate-400">Productos y cantidades exactas requeridas para cotización formal</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $quotationRequest->details->count() }} producto(s)
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">#</th>
                        <th class="py-3.5 px-6">Producto</th>
                        <th class="py-3.5 px-6">Unidad de Medida</th>
                        <th class="py-3.5 px-6 text-center">Cantidad Solicitada</th>
                        <th class="py-3.5 px-6">Notas Específicas</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($quotationRequest->details as $idx => $detail)
                        @php
                            $product = $detail->purchaseRequestDetail?->product;
                            $unit = $detail->purchaseRequestDetail?->unit;
                        @endphp
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6 font-mono text-xs text-slate-400">{{ $idx + 1 }}</td>
                            <td class="py-4 px-6">
                                <span class="font-extrabold text-slate-800 dark:text-white block">{{ $product->name ?? 'Producto no especificado' }}</span>
                                @if($product?->sku || $product?->barcode)
                                    <span class="text-xs font-mono text-slate-400">SKU: {{ $product->sku ?? $product->barcode }}</span>
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
                            <td class="py-4 px-6 text-xs text-slate-500 dark:text-slate-400">
                                {{ $detail->notes ?: 'Sin notas adicionales' }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="py-8 text-center text-slate-400">
                                No se encontraron ítems vinculados a esta solicitud de cotización.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Instrucciones y Seguimiento de Cotización Recibida -->
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- Instrucciones enviadas -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-2">
            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Instrucciones o Condiciones al Proveedor</h3>
            <p class="text-sm text-slate-700 dark:text-slate-300 italic">
                {{ $quotationRequest->notes ?: 'No se registraron instrucciones adicionales para esta invitación.' }}
            </p>
        </div>

        <!-- Seguimiento de la Cotización Formal -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-3">
            <h3 class="text-xs font-extrabold text-slate-400 uppercase tracking-wider">Seguimiento de Cotización Recibida</h3>
            @if($quotationRequest->id_purchase_quotation)
                <div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950/30 border border-emerald-200 dark:border-emerald-800 text-emerald-800 dark:text-emerald-300 text-sm font-semibold flex items-center gap-3">
                    <svg class="w-6 h-6 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    <div>
                        <span class="block font-bold">Cotización Formal Recibida</span>
                        <span class="text-xs opacity-80">ID de Cotización: #{{ $quotationRequest->id_purchase_quotation }}</span>
                    </div>
                </div>
            @else
                <div class="p-4 rounded-xl bg-slate-50 dark:bg-slate-800/60 border border-dashed border-slate-200 dark:border-slate-700 text-slate-600 dark:text-slate-400 text-sm space-y-1">
                    <div class="flex items-center gap-2 font-bold text-slate-700 dark:text-slate-300">
                        <span class="w-2 h-2 rounded-full bg-amber-500 animate-pulse"></span>
                        <span>Esperando Cotización del Proveedor</span>
                    </div>
                    <p class="text-xs text-slate-400">
                        La invitación formal fue registrada. Cuando el proveedor suministre precios y condiciones, su respuesta se vinculará formalmente a través del campo <code class="font-mono text-xs bg-slate-200 dark:bg-slate-700 px-1 py-0.5 rounded">id_purchase_quotation</code>.
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
