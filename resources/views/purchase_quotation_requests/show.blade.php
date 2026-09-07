@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Cotización')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-between shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif

    <!-- Encabezado con Botones de Acción -->
    <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
        <div>
            <div class="flex items-center gap-2">
                <span class="px-2.5 py-1 text-xs font-extrabold uppercase tracking-wider bg-teal-100 text-[#005e66] dark:bg-teal-900/40 dark:text-teal-300 rounded-lg">Solicitudes de Cotización</span>
                <span class="text-slate-400 dark:text-slate-600">•</span>
                <span class="text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase">Detalle</span>
            </div>
            <h1 class="text-3xl font-extrabold text-[#005e66] dark:text-teal-400 tracking-tight mt-1">
                Solicitud de Cotización #{{ str_pad($purchaseQuotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT) }}
            </h1>
            <p class="text-slate-500 dark:text-slate-400 text-xs mt-1">
                Registrada el {{ $purchaseQuotationRequest->created_at->format('d/m/Y \a \l\a\s h:i A') }}
            </p>
        </div>
        <div class="flex flex-wrap items-center gap-3 w-full md:w-auto">
            <button type="button" onclick="openProviderOfferModal()" class="flex-1 md:flex-none bg-[#005e66] hover:bg-[#00474f] text-white font-bold px-5 py-2.5 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <span>Registrar Oferta de Proveedor</span>
            </button>
            <a href="{{ route('purchase-quotation-requests.index') }}" class="flex-1 md:flex-none px-5 py-2.5 rounded-full bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-sm">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Tarjetas Superiores de Información -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Tarjeta de Solicitud de Compra Origen -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm p-6 space-y-4 md:col-span-2">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    📋
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitud de Compra Origen</h2>
                    <span class="text-xs text-slate-400">Documento base (id_purchase_request)</span>
                </div>
            </div>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">ID SOLICITUD COMPRA (ID_PURCHASE_REQUEST):</span>
                        <span class="font-mono font-extrabold text-slate-800 dark:text-slate-200 bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-xs">
                            #{{ $purchaseQuotationRequest->purchaseRequest->id_purchase_request ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">CÓDIGO SOLICITUD:</span>
                        <span class="font-mono font-extrabold text-[#005e66] dark:text-teal-300 bg-teal-50 dark:bg-teal-950 px-2.5 py-0.5 rounded-lg border border-teal-200 dark:border-teal-800">
                            {{ $purchaseQuotationRequest->purchaseRequest->purchase_request_code ?? 'N/A' }}
                        </span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">SUCURSAL DESTINO:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $purchaseQuotationRequest->purchaseRequest->branch->name ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">BODEGA:</span>
                        <span class="font-medium text-slate-700 dark:text-slate-300">{{ $purchaseQuotationRequest->purchaseRequest->warehouse->name ?? 'N/A' }}</span>
                    </div>
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">FECHA REQUERIDA:</span>
                        <span class="font-bold text-slate-700 dark:text-slate-300">
                            {{ $purchaseQuotationRequest->purchaseRequest?->required_date ? $purchaseQuotationRequest->purchaseRequest->required_date->format('d/m/Y') : 'N/A' }}
                        </span>
                    </div>
                </div>
                <div class="sm:col-span-2 pt-2 border-t border-slate-100 dark:border-slate-800">
                    <span class="text-xs font-bold text-slate-400 uppercase block mb-1">JUSTIFICACIÓN:</span>
                    <p class="text-xs text-slate-600 dark:text-slate-300 bg-slate-50 dark:bg-slate-800/50 p-2.5 rounded-xl border border-slate-100 dark:border-slate-800">
                        {{ $purchaseQuotationRequest->purchaseRequest->justification ?? 'Sin justificación registrada.' }}
                    </p>
                </div>
            </div>
        </div>

        <!-- Tarjeta de Cotización Recibida Asociada -->
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
                    <span class="text-xs font-bold text-slate-400 uppercase">ID COTIZACIÓN (ID_PURCHASE_QUOTATION):</span>
                    <span class="font-mono text-sm font-extrabold text-slate-800 dark:text-white">
                        {{ $purchaseQuotationRequest->id_purchase_quotation ? '#' . $purchaseQuotationRequest->id_purchase_quotation : 'Ninguna (NULL)' }}
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
                        <th class="py-3.5 px-6">ID DETALLE COT. REQ.</th>
                        <th class="py-3.5 px-6">ID DETALLE SOL. COMPRA</th>
                        <th class="py-3.5 px-6">PRODUCTO</th>
                        <th class="py-3.5 px-6">UNIDAD</th>
                        <th class="py-3.5 px-6 text-center">CANTIDAD (QUANTITY)</th>
                        <th class="py-3.5 px-6 text-center">ID DETALLE COTIZACIÓN</th>
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

    <!-- Ofertas de Proveedores Registradas -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-sm overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Ofertas Recibidas de Proveedores</h3>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-[#005e66]/10 text-[#005e66] dark:bg-teal-900/30 dark:text-teal-300">
                {{ count($supplierQuotations ?? []) }} {{ count($supplierQuotations ?? []) === 1 ? 'Oferta' : 'Ofertas' }}
            </span>
        </div>

        @if(isset($supplierQuotations) && count($supplierQuotations) > 0)
            <div class="space-y-4">
                @foreach($supplierQuotations as $quotation)
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 space-y-3 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-3">
                            <div>
                                <div class="flex items-center gap-2">
                                    <span class="font-extrabold text-slate-800 dark:text-white text-base">
                                        {{ $quotation->supplier->name ?? 'Proveedor' }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ $quotation->purchase_quotation_code }}
                                    </span>
                                </div>
                                <div class="text-xs text-slate-500 dark:text-slate-400 mt-1 flex flex-wrap gap-4">
                                    <span>📅 Fecha: <strong>{{ $quotation->quotation_date ? $quotation->quotation_date->format('d/m/Y') : 'N/A' }}</strong></span>
                                    <span>⏳ Vigencia: <strong>{{ $quotation->valid_until ? $quotation->valid_until->format('d/m/Y') : 'N/A' }}</strong></span>
                                    <span>💳 Pago: <strong>{{ $quotation->payment_terms ?? 'N/A' }}</strong></span>
                                    <span>🚚 Entrega: <strong>{{ $quotation->delivery_days ? $quotation->delivery_days . ' días' : 'N/A' }}</strong></span>
                                </div>
                            </div>
                            <div class="text-right flex items-center gap-3">
                                <div>
                                    <span class="text-xs uppercase text-slate-400 block font-extrabold">Total General</span>
                                    <span class="text-xl font-extrabold text-[#005e66] dark:text-teal-400">
                                        {{ $quotation->currency ?? 'USD' }} ${{ number_format($quotation->total, 2) }}
                                    </span>
                                </div>
                                <form action="{{ route('purchase-quotations.destroy', $quotation->id_purchase_quotation) }}" method="POST" onsubmit="return confirm('¿Está seguro de eliminar esta oferta de proveedor?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" title="Eliminar oferta" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </form>
                            </div>
                        </div>

                        <!-- Detalle Ítems Cotizados -->
                        <div class="overflow-x-auto">
                            <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                                <thead class="bg-slate-100 dark:bg-slate-800 text-[#005e66] dark:text-teal-400 font-extrabold uppercase">
                                    <tr>
                                        <th class="py-2 px-3">Producto</th>
                                        <th class="py-2 px-3 text-center">Cant.</th>
                                        <th class="py-2 px-3 text-right">Precio Unit.</th>
                                        <th class="py-2 px-3 text-right">Desc.</th>
                                        <th class="py-2 px-3 text-right">Impuesto</th>
                                        <th class="py-2 px-3 text-right">Subtotal</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-slate-200/60 dark:divide-slate-700/60">
                                    @foreach($quotation->details as $qDetail)
                                        <tr>
                                            <td class="py-2 px-3 font-semibold">{{ $qDetail->product->name ?? 'Producto' }}</td>
                                            <td class="py-2 px-3 text-center font-mono">{{ number_format($qDetail->quantity, 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono">${{ number_format($qDetail->unit_price, 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono">${{ number_format($qDetail->discount, 2) }}</td>
                                            <td class="py-2 px-3 text-right font-mono">${{ number_format($qDetail->tax_amount, 2) }} ({{ number_format($qDetail->tax_rate, 0) }}%)</td>
                                            <td class="py-2 px-3 text-right font-mono font-bold text-slate-800 dark:text-white">${{ number_format($qDetail->total, 2) }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @if($quotation->expenses && count($quotation->expenses) > 0)
                            <div class="pt-2 border-t border-slate-200/60 dark:border-slate-700/60 text-xs">
                                <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Gastos Adicionales:</span>
                                <div class="flex flex-wrap gap-3">
                                    @foreach($quotation->expenses as $qExpense)
                                        <span class="bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-700 px-2.5 py-1 rounded-lg text-slate-600 dark:text-slate-300">
                                            <strong>{{ $qExpense->expenseType->name ?? 'Gasto' }}:</strong> ${{ number_format($qExpense->amount, 2) }} {{ $qExpense->description ? "({$qExpense->description})" : '' }}
                                        </span>
                                    @endforeach
                                </div>
                            </div>
                        @endif
                    </div>
                @endforeach
            </div>
        @else
            <div class="text-center py-8 text-slate-400 text-xs">
                Aún no se han registrado ofertas de proveedores para esta solicitud. Haga clic en <strong>"Registrar Oferta de Proveedor"</strong> arriba para ingresar una oferta.
            </div>
        @endif
    </div>
</div>

{{-- MODAL PARA REGISTRAR OFERTA DE PROVEEDOR --}}
<div id="provider-offer-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-sm p-4">
    <div id="provider-offer-card" class="w-full max-w-4xl bg-white dark:bg-slate-800 rounded-2xl border border-slate-200 dark:border-slate-700 shadow-2xl transform scale-95 transition-all duration-200 overflow-hidden max-h-[90vh] flex flex-col">
        <div class="flex items-center justify-between px-6 py-4 border-b border-slate-200 dark:border-slate-700 shrink-0">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-slate-100">Registrar Oferta de Proveedor</h2>
                <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">Ingrese precios unitarios, descuentos, impuestos, condiciones y gastos adicionales del proveedor.</p>
            </div>
            <button type="button" onclick="closeProviderOfferModal()" class="text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 transition-colors p-1">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>

        <form method="POST" action="{{ route('purchase-quotations.store') }}" class="flex flex-col flex-1 overflow-hidden">
            @csrf
            <input type="hidden" name="id_purchase_quotation_request" value="{{ $purchaseQuotationRequest->id_purchase_quotation_request }}">

            <div class="p-6 space-y-5 overflow-y-auto flex-1">
                <!-- Sección 1: Datos Generales de la Cotización -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div class="space-y-1 md:col-span-2">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Proveedor <span class="text-rose-500">*</span></label>
                        <select name="id_supplier" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                            <option value="">-- Seleccionar Proveedor --</option>
                            @foreach($suppliers ?? [] as $supplier)
                                <option value="{{ $supplier->id_supplier }}">{{ $supplier->name }} {{ $supplier->code ? "({$supplier->code})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Moneda <span class="text-rose-500">*</span></label>
                        <select name="currency" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                            <option value="USD">USD ($)</option>
                            <option value="NIO">NIO (C$)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="CRC">CRC (₡)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Fecha Cotización <span class="text-rose-500">*</span></label>
                        <input type="date" name="quotation_date" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Válida Hasta</label>
                        <input type="date" name="valid_until" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Días de Entrega Global</label>
                        <input type="number" min="0" name="delivery_days" placeholder="Ej. 7" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>

                    <div class="space-y-1 md:col-span-3">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Condiciones de Pago</label>
                        <input type="text" name="payment_terms" placeholder="Ej. Crédito a 30 días, 50% anticipo 50% contra entrega..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none">
                    </div>
                </div>

                <!-- Sección 2: Precios y Detalles por Producto -->
                <div class="space-y-2 pt-2">
                    <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Detalle de Ítems Cotizados</label>
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl overflow-hidden overflow-x-auto">
                        <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300 min-w-[700px]">
                            <thead class="bg-slate-50 dark:bg-slate-900/80 font-extrabold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                                <tr>
                                    <th class="py-2.5 px-3">Producto</th>
                                    <th class="py-2.5 px-3 text-center">Cant. Solicitada</th>
                                    <th class="py-2.5 px-3 text-center">Precio Unit. ($) <span class="text-rose-500">*</span></th>
                                    <th class="py-2.5 px-3 text-center">Descuento ($)</th>
                                    <th class="py-2.5 px-3 text-center">Impuesto (%)</th>
                                    <th class="py-2.5 px-3 text-center">Días Entrega</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                                @foreach($details ?? [] as $index => $detail)
                                    @php
                                        $productObj = $detail->purchaseRequestDetail?->product ?? $detail->product;
                                        $unitObj = $detail->purchaseRequestDetail?->unit ?? $detail->unit;
                                    @endphp
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">
                                            {{ $productObj->name ?? 'Producto' }}
                                            <input type="hidden" name="items[{{ $index }}][id_product]" value="{{ $productObj->id }}">
                                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $detail->quantity }}">
                                            <input type="hidden" name="items[{{ $index }}][id_unit]" value="{{ $unitObj->id ?? '' }}">
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-mono font-bold">
                                            {{ number_format($detail->quantity, 2) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.0001" min="0" name="items[{{ $index }}][unit_price]" required placeholder="0.00" class="w-28 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][discount]" value="0.00" class="w-24 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_rate]" value="15.00" class="w-20 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" min="0" name="items[{{ $index }}][delivery_days]" placeholder="Días" class="w-20 px-2 py-1 rounded border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Sección 3: Gastos Adicionales -->
                <div class="space-y-2 pt-2">
                    <div class="flex items-center justify-between">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Gastos Adicionales</label>
                        <button type="button" onclick="addExpenseRow()" class="text-xs font-bold text-[#005e66] dark:text-teal-400 hover:underline flex items-center gap-1">
                            + Agregar Gasto
                        </button>
                    </div>

                    <div id="expenses-container" class="space-y-2">
                        <!-- Filas de gastos dinámicos creadas vía JS -->
                    </div>
                </div>

                <!-- Sección 4: Notas de la oferta -->
                <div class="space-y-1 pt-1">
                    <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Notas Adicionales de la Oferta</label>
                    <textarea name="notes" rows="2" placeholder="Observaciones o aclaraciones de la cotización..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-none"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <button type="button" onclick="closeProviderOfferModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-xs font-bold transition-all shadow-sm">
                    Guardar Oferta
                </button>
            </div>
        </form>
    </div>
</div>

<script>
let expenseIndex = 0;
const expenseTypes = @json($expenseTypes ?? []);

function openProviderOfferModal() {
    const modal = document.getElementById('provider-offer-modal');
    const card = document.getElementById('provider-offer-card');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => {
        card.classList.remove('scale-95');
        card.classList.add('scale-100');
    }, 10);
}

function closeProviderOfferModal() {
    const modal = document.getElementById('provider-offer-modal');
    const card = document.getElementById('provider-offer-card');
    card.classList.remove('scale-100');
    card.classList.add('scale-95');
    setTimeout(() => {
        modal.classList.add('hidden');
        modal.classList.remove('flex');
    }, 150);
}

function addExpenseRow() {
    const container = document.getElementById('expenses-container');
    const div = document.createElement('div');
    div.className = 'grid grid-cols-1 md:grid-cols-12 gap-2 items-center border border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-slate-50 dark:bg-slate-900';

    let selectOptions = '<option value="">-- Tipo de Gasto --</option>';
    expenseTypes.forEach(t => {
        selectOptions += `<option value="${t.id_expense_type}">${t.name}</option>`;
    });

    div.innerHTML = `
        <div class="md:col-span-4">
            <select name="expenses[${expenseIndex}][id_expense_type]" required class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
                ${selectOptions}
            </select>
        </div>
        <div class="md:col-span-5">
            <input type="text" name="expenses[${expenseIndex}][description]" placeholder="Descripción del gasto (ej. flete)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-none">
        </div>
        <div class="md:col-span-2">
            <input type="number" step="0.01" min="0" name="expenses[${expenseIndex}][amount]" required placeholder="Monto $" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white text-right focus:ring-1 focus:ring-[#005e66] outline-none">
        </div>
        <div class="md:col-span-1 text-center">
            <button type="button" onclick="this.closest('.grid').remove()" class="text-rose-500 hover:text-rose-700 p-1">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
        </div>
    `;

    container.appendChild(div);
    expenseIndex++;
}
</script>
@endsection