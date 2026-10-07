@extends('layouts.app')
@section('title', 'Detalle de Solicitud de Cotización')

@section('content')
<div class="w-full space-y-6 animate-fade-in duration-300">
    @if(session('success'))
        <div class="bg-emerald-50 dark:bg-emerald-950/50 border border-emerald-200 dark:border-emerald-800 text-emerald-700 dark:text-emerald-300 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ session('success') }}</span>
            </div>
        </div>
    @endif
    @if(session('error'))
        <div class="bg-rose-50 dark:bg-rose-950/50 border border-rose-200 dark:border-rose-800 text-rose-700 dark:text-rose-300 px-4 py-3 rounded-xl text-sm font-bold flex items-center justify-between shadow-xs">
            <div class="flex items-center gap-2">
                <svg class="w-5 h-5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span>{{ session('error') }}</span>
            </div>
        </div>
    @endif

    @php
        // Adjudicada: cada línea apunta a la línea ganadora de alguna oferta
        $awarded = $purchaseQuotationRequest->isAwarded();
        $awardedDetailIds = $purchaseQuotationRequest->details->pluck('id_purchase_quotation_detail')->filter()->unique();
    @endphp

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
            @if(! $awarded)
                <button type="button" onclick="openProviderOfferModal()" class="flex-1 md:flex-none bg-customTeal-800 hover:bg-navy-800 text-white font-bold px-5 py-2.5 rounded-full shadow-lg transition-all flex items-center justify-center gap-2 text-xs">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <span>Registrar Oferta de Proveedor</span>
                </button>
            @else
                <span class="flex-1 md:flex-none px-4 py-2.5 rounded-full bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300 font-extrabold text-xs border border-emerald-300 dark:border-emerald-700 flex items-center justify-center gap-1.5 shadow-xs">
                    <svg class="w-4 h-4 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <span>Adjudicada - Registro Cerrado</span>
                </span>
            @endif
            <a href="{{ route('purchase-quotation-requests.index') }}" class="flex-1 md:flex-none px-5 py-2.5 rounded-full bg-slate-800 dark:bg-slate-700 hover:bg-slate-700 dark:hover:bg-slate-600 text-white font-bold text-xs transition-all flex items-center justify-center gap-2 shadow-xs">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
                <span>Volver al Listado</span>
            </a>
        </div>
    </div>

    <!-- Tarjetas Superiores de Información -->
    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
        <!-- Tarjeta de Solicitudes de Compra Origen -->
        @php $originRequests = $purchaseQuotationRequest->purchaseRequests; @endphp
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6 space-y-4 md:col-span-2">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    📋
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Solicitudes de Compra</h2>
                    <span class="text-xs text-slate-400">{{ $originRequests->count() }} {{ $originRequests->count() === 1 ? 'solicitud reunida' : 'solicitudes reunidas' }} en esta cotización</span>
                </div>
            </div>
            <div class="space-y-3">
                @forelse($originRequests as $originRequest)
                    <div class="p-3 rounded-xl bg-slate-50 dark:bg-slate-800/50 border border-slate-100 dark:border-slate-800 text-sm">
                        <div class="flex flex-wrap items-center justify-between gap-2">
                            <span class="font-mono font-extrabold text-[#005e66] dark:text-teal-300 bg-teal-50 dark:bg-teal-950 px-2.5 py-0.5 rounded-lg border border-teal-200 dark:border-teal-800">
                                {{ $originRequest->purchase_request_code }}
                            </span>
                            <span class="text-xs font-bold text-slate-500 dark:text-slate-400">
                                Requerida: {{ $originRequest->required_date?->format('d/m/Y') ?? 'N/A' }}
                            </span>
                        </div>
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-2 mt-2 text-xs">
                            <div><span class="font-bold text-slate-400 uppercase">Sucursal:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $originRequest->branch?->name ?? 'N/A' }}</span></div>
                            <div><span class="font-bold text-slate-400 uppercase">Bodega:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $originRequest->warehouse?->name ?? 'N/A' }}</span></div>
                            <div><span class="font-bold text-slate-400 uppercase">Solicitante:</span> <span class="font-medium text-slate-700 dark:text-slate-300">{{ $originRequest->user?->username ?? 'N/A' }}</span></div>
                        </div>
                        <p class="text-xs text-slate-600 dark:text-slate-300 mt-2">{{ $originRequest->justification ?? 'Sin justificación registrada.' }}</p>
                    </div>
                @empty
                    <p class="text-xs text-slate-400">No hay solicitudes de compra vinculadas.</p>
                @endforelse
            </div>
        </div>

        <!-- Tarjeta de Adjudicación -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6 space-y-4">
            <div class="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-3">
                <div class="w-10 h-10 rounded-xl bg-teal-50 dark:bg-teal-900/30 text-[#005e66] dark:text-teal-400 flex items-center justify-center text-lg font-bold">
                    🏆
                </div>
                <div>
                    <h2 class="text-base font-extrabold text-slate-800 dark:text-white">Adjudicación</h2>
                    <span class="text-xs text-slate-400">Proveedor que gana cada producto</span>
                </div>
            </div>
            <div class="space-y-2.5 text-sm">
                @if($awarded)
                    @foreach($supplierQuotations as $winner)
                        @php $won = $winner->details->whereIn('id_purchase_quotation_detail', $awardedDetailIds); @endphp
                        @if($won->isNotEmpty())
                            <div class="p-2.5 rounded-xl bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800">
                                <div class="flex justify-between items-center gap-2">
                                    <span class="font-bold text-slate-800 dark:text-white text-xs">{{ $winner->supplier->name ?? 'Proveedor' }}</span>
                                    <span class="font-mono text-xs font-extrabold text-emerald-700 dark:text-emerald-300">${{ number_format($won->sum('total'), 2) }}</span>
                                </div>
                                <span class="text-xs text-slate-500 dark:text-slate-400">{{ $won->count() }} {{ $won->count() === 1 ? 'producto' : 'productos' }} · {{ $winner->purchase_quotation_code }}</span>
                            </div>
                        @endif
                    @endforeach
                    <div class="pt-2 border-t border-slate-100 dark:border-slate-800 flex justify-end">
                        <a href="{{ route('purchase_orders.index') }}" class="w-full text-center px-3 py-2 rounded-xl bg-customTeal-800 hover:bg-navy-800 text-white text-xs font-bold transition-all shadow-xs flex items-center justify-center gap-1">
                            📦 Crear Órdenes de Compra
                        </a>
                    </div>
                @else
                    <div class="flex justify-between items-center">
                        <span class="text-xs font-bold text-slate-400 uppercase">ESTADO:</span>
                        <span class="font-bold text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-950 px-2 py-0.5 rounded-sm text-xs">
                            Pendiente de adjudicar
                        </span>
                    </div>
                    <p class="text-xs text-slate-400 pt-2 border-t border-slate-100 dark:border-slate-800">
                        Elija en <strong>"Adjudicación por Producto"</strong>, debajo de las ofertas, qué proveedor gana cada producto.
                    </p>
                @endif
            </div>
        </div>
    </div>

    <!-- Tabla de Ítems Solicitados a Cotizar -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden">
        <div class="p-6 border-b border-slate-100 dark:border-slate-800 flex justify-between items-center">
            <div>
                <h2 class="text-lg font-extrabold text-slate-800 dark:text-white">Productos a Cotizar</h2>
                <p class="text-xs text-slate-400">Una línea por producto con la cantidad total de todas las solicitudes de compra.</p>
            </div>
            <span class="px-3 py-1 rounded-full text-xs font-extrabold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                {{ $lines->count() }} producto(s)
            </span>
        </div>
        <div class="overflow-x-auto">
            <table class="w-full text-left text-sm text-slate-600 dark:text-slate-300">
                <thead class="bg-slate-50/80 dark:bg-slate-800/80 text-xs uppercase font-extrabold text-slate-400 dark:text-slate-500 tracking-wider border-b border-slate-100 dark:border-slate-800">
                    <tr>
                        <th class="py-3.5 px-6">PRODUCTO</th>
                        <th class="py-3.5 px-6">UNIDAD</th>
                        <th class="py-3.5 px-6 text-center">CANTIDAD TOTAL</th>
                        <th class="py-3.5 px-6">POR SOLICITUD</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                    @forelse($lines as $line)
                        <tr class="hover:bg-slate-50/60 dark:hover:bg-slate-800/40 transition-colors">
                            <td class="py-4 px-6">
                                <span class="font-extrabold text-slate-800 dark:text-white block">{{ $line->product?->name ?? 'Producto no especificado' }}</span>
                                @if($line->product?->sku)
                                    <span class="text-xs font-mono text-slate-400">SKU: {{ $line->product->sku }}</span>
                                @endif
                            </td>
                            <td class="py-4 px-6">
                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-md text-xs font-bold bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300">
                                    {{ $line->unit?->name ?? 'Unidad' }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-center">
                                <span class="font-mono text-base font-extrabold text-slate-800 dark:text-white">
                                    {{ number_format($line->quantity, 2) }}
                                </span>
                            </td>
                            <td class="py-4 px-6 text-xs text-slate-500 dark:text-slate-400">
                                @foreach($line->sources as $source)
                                    <span class="block">
                                        <span class="font-mono font-bold text-slate-700 dark:text-slate-300">{{ $source->purchaseRequest?->purchase_request_code }}</span>
                                        ({{ $source->purchaseRequest?->branch?->name ?? 'Sin sucursal' }}): {{ number_format($source->quantity, 2) }}
                                    </span>
                                @endforeach
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="py-8 text-center text-slate-400">
                                No se encontraron productos para esta solicitud de cotización.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Ofertas de Proveedores Registradas -->
    <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs overflow-hidden p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Ofertas Recibidas de Proveedores</h3>
            <span class="px-2.5 py-1 rounded-full text-xs font-bold bg-customTeal-800/10 text-[#005e66] dark:bg-teal-900/30 dark:text-teal-300">
                {{ count($supplierQuotations ?? []) }} {{ count($supplierQuotations ?? []) === 1 ? 'Oferta' : 'Ofertas' }}
            </span>
        </div>

        @if(isset($supplierQuotations) && count($supplierQuotations) > 0)
            <div class="space-y-4">
                @foreach($supplierQuotations as $quotation)
                    <div class="border border-slate-200 dark:border-slate-700 rounded-xl p-4 space-y-3 bg-slate-50/50 dark:bg-slate-800/40">
                        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-2 border-b border-slate-200 dark:border-slate-700 pb-3">
                            <div>
                                <div class="flex items-center gap-2 flex-wrap">
                                    <span class="font-extrabold text-slate-800 dark:text-white text-base">
                                        {{ $quotation->supplier->name ?? 'Proveedor' }}
                                    </span>
                                    <span class="text-xs font-semibold px-2 py-0.5 rounded-sm bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-300">
                                        {{ $quotation->purchase_quotation_code }}
                                    </span>

                                    @if($awarded)
                                        @php $wonCount = $quotation->details->whereIn('id_purchase_quotation_detail', $awardedDetailIds)->count(); @endphp
                                        @if($wonCount > 0)
                                            <span class="px-2.5 py-1 bg-emerald-100 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-300 text-xs font-extrabold rounded-lg border border-emerald-300 dark:border-emerald-700 flex items-center gap-1">
                                                ✓ Adjudicada: {{ $wonCount }} de {{ $quotation->details->count() }} productos
                                            </span>
                                        @else
                                            <span class="px-2.5 py-1 bg-slate-100 dark:bg-slate-800 text-slate-400 dark:text-slate-500 text-xs font-semibold rounded-lg border border-slate-200 dark:border-slate-700">
                                                No adjudicada
                                            </span>
                                        @endif
                                    @endif
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
                                @if(! $awarded)
                                    <button type="button" onclick="confirmDelete('{{ route('purchase-quotations.destroy', $quotation->id_purchase_quotation) }}', 'Oferta de {{ addslashes($quotation->supplier->name ?? 'Proveedor') }}', 'delete')" title="Eliminar oferta" class="p-1.5 rounded-lg text-rose-500 hover:bg-rose-50 dark:hover:bg-rose-950/40 transition-colors">
                                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
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
                                            <td class="py-2 px-3 font-semibold">
                                                {{ $qDetail->product->name ?? 'Producto' }}
                                                @if($awardedDetailIds->contains($qDetail->id_purchase_quotation_detail))
                                                    <span class="ml-1 text-emerald-600 dark:text-emerald-400 font-extrabold">✓ Adjudicado</span>
                                                @endif
                                            </td>
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

    @if(! $awarded && count($supplierQuotations) > 0)
        @can('purchase_quotation_requests.seleccionar_cotizacion')
        <!-- Adjudicación por Producto -->
        <div class="bg-white dark:bg-slate-900 rounded-2xl border border-slate-100 dark:border-slate-800 shadow-xs p-6 space-y-4">
            <div>
                <h3 class="text-base font-extrabold text-slate-800 dark:text-white">Adjudicación por Producto</h3>
                <p class="text-xs text-slate-400">Elija qué proveedor gana cada producto: todo a uno solo o repartido entre varios. El menor total de cada producto está resaltado.</p>
            </div>
            <form id="award-form" method="POST" action="{{ route('purchase-quotation-requests.award', $purchaseQuotationRequest->id_purchase_quotation_request) }}">
                @csrf
                <div class="overflow-x-auto border border-slate-200 dark:border-slate-700 rounded-xl">
                    <table class="w-full text-left text-xs text-slate-600 dark:text-slate-300">
                        <thead class="bg-slate-50 dark:bg-slate-800/80 font-extrabold text-slate-400 uppercase border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="py-3 px-4">Producto</th>
                                <th class="py-3 px-4 text-center">Cantidad</th>
                                @foreach($supplierQuotations as $quotation)
                                    <th class="py-3 px-4 text-center normal-case">
                                        <span class="block font-extrabold text-slate-700 dark:text-slate-200">{{ $quotation->supplier->name ?? 'Proveedor' }}</span>
                                        <span class="block font-mono text-slate-400">{{ $quotation->purchase_quotation_code }}</span>
                                        <button type="button" onclick="awardAllTo({{ $quotation->id_purchase_quotation }})" class="mt-1.5 px-2.5 py-1 rounded-lg bg-teal-50 dark:bg-teal-950 text-[#005e66] dark:text-teal-300 hover:bg-customTeal-800 hover:text-white border border-teal-200 dark:border-teal-800 text-[10px] font-extrabold transition-all">Todo de este proveedor</button>
                                    </th>
                                @endforeach
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-700">
                            @foreach($lines as $line)
                                @php
                                    // Línea de cada oferta para este producto (y su unidad, si la indicó)
                                    $lineOffers = $supplierQuotations->mapWithKeys(fn ($quotation) => [
                                        $quotation->id_purchase_quotation => $quotation->details->first(fn ($d) => (int) $d->id_product === (int) $line->product?->id_product
                                            && ($d->id_unit === null || (int) $d->id_unit === (int) $line->unit?->id_unit)),
                                    ]);
                                    $bestTotal = $lineOffers->filter()->min(fn ($d) => (float) $d->total);
                                @endphp
                                <tr>
                                    <td class="py-3 px-4">
                                        <span class="font-bold text-slate-800 dark:text-white block">{{ $line->product?->name ?? 'Producto' }}</span>
                                        <span class="text-slate-400">{{ $line->unit?->name }}</span>
                                    </td>
                                    <td class="py-3 px-4 text-center font-mono font-bold">{{ number_format($line->quantity, 2) }}</td>
                                    @foreach($supplierQuotations as $quotation)
                                        @php $offer = $lineOffers[$quotation->id_purchase_quotation]; @endphp
                                        <td class="py-3 px-4 text-center">
                                            @if($offer)
                                                <label class="inline-flex flex-col items-center gap-1 cursor-pointer px-3 py-2 rounded-lg {{ (float) $offer->total === (float) $bestTotal ? 'bg-emerald-50 dark:bg-emerald-950/40 ring-1 ring-emerald-200 dark:ring-emerald-800' : '' }}">
                                                    <input type="radio" name="awards[{{ $line->key }}]" value="{{ $offer->id_purchase_quotation_detail }}" required
                                                        data-quotation="{{ $quotation->id_purchase_quotation }}" data-supplier="{{ $quotation->supplier->name ?? 'Proveedor' }}" data-total="{{ $offer->total }}"
                                                        class="award-option w-4 h-4 text-[#005e66] border-slate-300 cursor-pointer">
                                                    <span class="font-mono">${{ number_format($offer->unit_price, 2) }} c/u</span>
                                                    <span class="font-mono font-extrabold text-slate-800 dark:text-white">${{ number_format($offer->total, 2) }}</span>
                                                </label>
                                            @else
                                                <span class="text-slate-400">No cotizó</span>
                                            @endif
                                        </td>
                                    @endforeach
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="flex flex-col sm:flex-row items-start sm:items-center justify-between gap-3 pt-4">
                    <p class="text-sm text-slate-600 dark:text-slate-300">
                        Total adjudicado: <strong id="award-total" class="font-mono text-[#005e66] dark:text-teal-400">$0.00</strong>
                        <span class="block text-xs text-slate-400">Productos con impuesto; los gastos adicionales se revisan en cada orden de compra.</span>
                    </p>
                    <button type="button" onclick="confirmAward()" class="px-5 py-2.5 rounded-xl bg-emerald-600 hover:bg-emerald-700 text-white font-bold text-sm transition shadow-md">Guardar Adjudicación</button>
                </div>
            </form>
        </div>

        <!-- MODAL CONFIRMAR ADJUDICACIÓN -->
        <div id="award-confirm-modal" class="hidden fixed inset-0 z-60 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-xs transition-all duration-200">
            <div id="award-confirm-card" class="bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-2xl p-8 max-w-md w-full shadow-2xl text-center relative mx-4 transform scale-95 transition-all duration-200">
                <div class="w-14 h-14 rounded-full border-2 border-emerald-400 flex items-center justify-center mx-auto text-emerald-500 dark:text-emerald-400 mb-5">
                    <svg class="w-7 h-7" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                </div>
                <h3 class="text-lg font-bold text-slate-800 dark:text-slate-100 mb-2">¿Guardar la adjudicación?</h3>
                <div id="award-confirm-summary" class="text-sm text-slate-600 dark:text-slate-300 space-y-1 mb-3"></div>
                <p class="text-slate-500 dark:text-slate-400 text-xs mb-6">Es definitiva: ya no se podrán registrar ni eliminar ofertas. De cada proveedor ganador saldrá una orden de compra con sus productos.</p>
                <div class="flex justify-center gap-3">
                    <button type="button" onclick="closeModal('award-confirm-modal')" class="px-5 py-2.5 bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 dark:hover:bg-slate-600 text-slate-700 dark:text-slate-200 font-semibold rounded-xl text-sm transition-all">Cancelar</button>
                    <button type="button" onclick="document.getElementById('award-form').submit()" class="px-5 py-2.5 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl text-sm transition-all shadow-xs">Sí, adjudicar</button>
                </div>
            </div>
        </div>
        @endcan
    @endif
</div>

{{-- MODAL PARA REGISTRAR OFERTA DE PROVEEDOR --}}
<div id="provider-offer-modal" class="hidden fixed inset-0 z-50 items-center justify-center bg-slate-900/60 dark:bg-black/80 backdrop-blur-xs p-4">
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
                        <select name="id_supplier" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
                            <option value="">-- Seleccionar Proveedor --</option>
                            @foreach($suppliers ?? [] as $supplier)
                                <option value="{{ $supplier->id_supplier }}">{{ $supplier->name }} {{ $supplier->code ? "({$supplier->code})" : '' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Moneda <span class="text-rose-500">*</span></label>
                        <select name="currency" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
                            <option value="USD">USD ($)</option>
                            <option value="NIO">NIO (C$)</option>
                            <option value="EUR">EUR (€)</option>
                            <option value="CRC">CRC (₡)</option>
                        </select>
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Fecha Cotización <span class="text-rose-500">*</span></label>
                        <input type="date" name="quotation_date" value="{{ date('Y-m-d') }}" required class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Válida Hasta</label>
                        <input type="date" name="valid_until" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
                    </div>

                    <div class="space-y-1">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Días de Entrega Global</label>
                        <input type="number" min="0" name="delivery_days" placeholder="Ej. 7" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
                    </div>

                    <div class="space-y-1 md:col-span-3">
                        <label class="block text-xs font-extrabold text-slate-500 uppercase tracking-wider">Condiciones de Pago</label>
                        <input type="text" name="payment_terms" placeholder="Ej. Crédito a 30 días, 50% anticipo 50% contra entrega..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden">
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
                                @foreach($lines as $index => $line)
                                    @php
                                        $productObj = $line->product;
                                        $unitObj = $line->unit;
                                    @endphp
                                    <tr>
                                        <td class="py-2.5 px-3 font-bold text-slate-800 dark:text-white">
                                            {{ $productObj->name ?? 'Producto' }}
                                            <input type="hidden" name="items[{{ $index }}][id_product]" value="{{ $productObj->id_product }}">
                                            <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $line->quantity }}">
                                            <input type="hidden" name="items[{{ $index }}][id_unit]" value="{{ $unitObj->id_unit ?? '' }}">
                                        </td>
                                        <td class="py-2.5 px-3 text-center font-mono font-bold">
                                            {{ number_format($line->quantity, 2) }}
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.0001" min="0" name="items[{{ $index }}][unit_price]" required placeholder="0.00" class="w-28 px-2 py-1 rounded-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center font-bold text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][discount]" value="0.00" class="w-24 px-2 py-1 rounded-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" step="0.01" min="0" name="items[{{ $index }}][tax_rate]" value="15.00" class="w-20 px-2 py-1 rounded-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
                                        </td>
                                        <td class="py-2.5 px-3 text-center">
                                            <input type="number" min="0" name="items[{{ $index }}][delivery_days]" placeholder="Días" class="w-20 px-2 py-1 rounded-sm border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-900 text-center text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
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
                    <textarea name="notes" rows="2" placeholder="Observaciones o aclaraciones de la cotización..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 dark:border-slate-700 bg-slate-50 dark:bg-slate-900 text-sm font-medium text-slate-800 dark:text-white focus:ring-2 focus:ring-[#005e66] outline-hidden"></textarea>
                </div>
            </div>

            <div class="flex justify-end gap-3 px-6 py-4 border-t border-slate-200 dark:border-slate-700 bg-slate-50/50 dark:bg-slate-900/50 shrink-0">
                <button type="button" onclick="closeProviderOfferModal()" class="px-5 py-2.5 rounded-xl bg-slate-100 hover:bg-slate-200 dark:bg-slate-700 text-slate-700 dark:text-slate-200 text-xs font-bold transition-all">
                    Cancelar
                </button>
                <button type="submit" class="px-6 py-2.5 rounded-xl bg-customTeal-800 hover:bg-navy-800 text-white text-xs font-bold transition-all shadow-xs">
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
    div.className = 'flex flex-row items-center gap-2 border border-slate-200 dark:border-slate-700 rounded-xl p-2 bg-slate-50 dark:bg-slate-900 w-full';
    div.style.cssText = 'display: flex !important; flex-direction: row !important; flex-wrap: nowrap !important; align-items: center !important;';

    let selectOptions = '<option value="">-- Tipo de Gasto --</option>';
    expenseTypes.forEach(t => {
        const descAttr = (t.description || t.name || '').replace(/"/g, '&quot;');
        selectOptions += `<option value="${t.id_expense_type}" data-description="${descAttr}">${t.name}</option>`;
    });

    const currentIndex = expenseIndex;

    div.innerHTML = `
        <div style="flex: 1 1 35%; min-width: 0;">
            <select name="expenses[${currentIndex}][id_expense_type]" required onchange="updateExpenseDescription(this, ${currentIndex})" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
                ${selectOptions}
            </select>
        </div>
        <div style="flex: 1 1 40%; min-width: 0;">
            <input type="text" name="expenses[${currentIndex}][description]" id="expense_desc_${currentIndex}" placeholder="Descripción del gasto (ej. flete)" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-medium text-slate-800 dark:text-white focus:ring-1 focus:ring-[#005e66] outline-hidden">
        </div>
        <div style="flex: 0 0 20%; min-width: 0;">
            <input type="number" step="0.01" min="0" name="expenses[${currentIndex}][amount]" required placeholder="Monto $" class="w-full px-2.5 py-1.5 rounded-lg border border-slate-200 dark:border-slate-700 bg-white dark:bg-slate-800 text-xs font-bold text-slate-800 dark:text-white text-right focus:ring-1 focus:ring-[#005e66] outline-hidden">
        </div>
        <div style="flex: 0 0 auto;" class="text-center">
            <button type="button" onclick="this.closest('.flex').remove()" class="text-rose-500 hover:text-rose-700 p-1" title="Eliminar gasto">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3M4 7h16"/></svg>
            </button>
        </div>
    `;

    container.appendChild(div);
    expenseIndex++;
}

function updateExpenseDescription(selectEl, index) {
    const descInput = document.getElementById(`expense_desc_${index}`);
    const selectedOpt = selectEl.options[selectEl.selectedIndex];
    if (descInput && selectedOpt && selectedOpt.value) {
        const fullDesc = selectedOpt.getAttribute('data-description');
        descInput.value = fullDesc || selectedOpt.text;
    }
}

// Adjudicación: todos los productos que cotizó un proveedor pasan a él
function awardAllTo(quotationId) {
    document.querySelectorAll('#award-form .award-option[data-quotation="' + quotationId + '"]').forEach(option => {
        option.checked = true;
    });
    updateAwardTotal();
}

function formatMoney(value) {
    return '$' + value.toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function updateAwardTotal() {
    const total = Array.from(document.querySelectorAll('.award-option:checked'))
        .reduce((sum, option) => sum + parseFloat(option.dataset.total || 0), 0);
    const totalEl = document.getElementById('award-total');
    if (totalEl) totalEl.textContent = formatMoney(total);
}

// Antes de guardar: todos los productos con proveedor, y un resumen por proveedor
function confirmAward() {
    const form = document.getElementById('award-form');
    if (!form || !form.reportValidity()) return;

    const bySupplier = new Map();
    document.querySelectorAll('.award-option:checked').forEach(option => {
        const entry = bySupplier.get(option.dataset.supplier) || { count: 0, total: 0 };
        entry.count++;
        entry.total += parseFloat(option.dataset.total || 0);
        bySupplier.set(option.dataset.supplier, entry);
    });

    const summary = document.getElementById('award-confirm-summary');
    summary.innerHTML = '';
    bySupplier.forEach((entry, supplier) => {
        const line = document.createElement('p');
        line.textContent = supplier + ': ' + entry.count + (entry.count === 1 ? ' producto' : ' productos') + ' (' + formatMoney(entry.total) + ')';
        summary.appendChild(line);
    });

    const modal = document.getElementById('award-confirm-modal');
    const card = document.getElementById('award-confirm-card');
    modal.classList.remove('hidden');
    modal.classList.add('flex');
    setTimeout(() => { card.classList.remove('scale-95'); card.classList.add('scale-100'); }, 10);
}

document.querySelectorAll('.award-option').forEach(option => option.addEventListener('change', updateAwardTotal));
</script>
@endsection