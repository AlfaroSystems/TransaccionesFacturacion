<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura de Compra {{ $purchase->purchase_code ?? 'CMP-' . $purchase->id_purchase }}</title>
    <!-- Estilos del build (sin depender de internet para imprimir) -->
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 11px !important;
                padding: 0 !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
                max-width: 100% !important;
            }
            @page {
                size: letter portrait;
                margin: 12mm 15mm;
            }
            tr {
                page-break-inside: avoid;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans p-4 md:p-8" onload="setTimeout(() => { window.print(); }, 500);">

    <!-- Barra de Acciones Superior (No Imprimible) -->
    <div class="max-w-5xl mx-auto mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-teal-100 text-[#005e66] font-extrabold text-xs rounded-lg uppercase tracking-wider">
                Documento de Recepción Fiscal
            </span>
            <span class="text-sm font-bold text-slate-700">Factura de Compra: {{ $purchase->purchase_code }}</span>
        </div>
        <div class="flex items-center gap-3">
            <button type="button" onclick="window.print()" class="px-5 py-2 rounded-xl bg-[#005e66] hover:bg-[#00474f] text-white text-xs font-bold transition-all shadow-md flex items-center gap-2">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                <span>Imprimir / Guardar como PDF</span>
            </button>
            <button type="button" onclick="window.close()" class="px-4 py-2 rounded-xl bg-slate-100 hover:bg-slate-200 text-slate-600 text-xs font-bold transition-all">
                Cerrar Ventana
            </button>
        </div>
    </div>

    <!-- Documento Imprimible -->
    <div class="max-w-5xl mx-auto bg-white p-8 md:p-12 rounded-3xl shadow-lg border border-slate-200 print-card space-y-6">
        
        <!-- Encabezado / Logo / Datos de la Empresa -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-200 pb-6 gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-12 h-12 rounded-xl bg-[#005e66] text-white flex items-center justify-center font-black text-2xl tracking-tighter">
                        ERP
                    </div>
                    <div>
                        <h1 class="text-lg font-black text-[#005e66] uppercase tracking-tight">
                            {{ $company->name ?? 'SISTEMA DE TRANSACCIONES & FACTURACIÓN' }}
                        </h1>
                        <p class="text-xs text-slate-500 font-semibold">
                            {{ $company->commercial_name ?? 'Comprobante de Recepción y Factura de Compra' }}
                        </p>
                    </div>
                </div>
                <div class="mt-3 text-xs text-slate-600 space-y-0.5">
                    @if($company?->nit || $company?->nrc)
                        <p><strong>NIT:</strong> {{ $company->nit ?? 'N/A' }} &nbsp;|&nbsp; <strong>NRC:</strong> {{ $company->nrc ?? 'N/A' }}</p>
                    @endif
                    @if($company?->addres)
                        <p><strong>Dirección:</strong> {{ $company->addres }}</p>
                    @endif
                    <p>
                        <strong>Sucursal Destino:</strong> {{ $purchase->branch->name ?? 'Sucursal Central' }} 
                        &nbsp;|&nbsp; 
                        <strong>Bodega de Entrada:</strong> {{ $purchase->warehouse->name ?? 'Bodega Principal' }}
                    </p>
                </div>
            </div>

            <div class="text-left sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto">
                <span class="px-3 py-1 text-xs font-extrabold uppercase rounded-lg border border-teal-300 bg-teal-50 text-[#005e66]">
                    FACTURA / COMPRA
                </span>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight mt-2 font-mono">
                    {{ $purchase->purchase_code }}
                </h2>
                <div class="text-xs text-slate-500 font-semibold mt-2 space-y-1">
                    <p>Fecha Recepción: <strong class="text-slate-800">{{ $purchase->purchase_date ? $purchase->purchase_date->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</strong></p>
                    @if($purchase->supplier_invoice_number)
                        <p>No. Factura Proveedor: <strong class="text-[#005e66]">{{ $purchase->supplier_invoice_number }}</strong></p>
                    @endif
                    @php
                        $statusLabels = [
                            'draft' => 'Borrador',
                            'received' => 'Recibida',
                            'completed' => 'Completada',
                            'cancelled' => 'Cancelada'
                        ];
                    @endphp
                    <p>Estado: <strong class="uppercase text-emerald-700 font-bold">{{ $statusLabels[$purchase->status] ?? ucfirst($purchase->status) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Información de Proveedor y Factura -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
            <div class="space-y-1">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">DATOS DEL PROVEEDOR</h3>
                <p class="font-bold text-slate-900 text-sm">{{ $purchase->supplier->name ?? 'N/A' }}</p>
                @if($purchase->supplier?->nit || $purchase->supplier?->nrc)
                    <p class="text-slate-600"><strong>NIT/NRC:</strong> {{ $purchase->supplier->nit ?? '' }} / {{ $purchase->supplier->nrc ?? '' }}</p>
                @endif
                <p class="text-slate-600"><strong>País:</strong> {{ $purchase->supplier->country ?? 'El Salvador' }}</p>
                <p class="text-slate-600"><strong>Tel:</strong> {{ $purchase->supplier->phone ?? 'N/A' }} &nbsp;|&nbsp; <strong>Email:</strong> {{ $purchase->supplier->email ?? 'N/A' }}</p>
            </div>

            <div class="space-y-1 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-4">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">COMPROBANTE FISCAL</h3>
                <p class="text-slate-700"><strong>No. Factura / CCF:</strong> {{ $purchase->supplier_invoice_number ?? 'N/A' }}</p>
                <p class="text-slate-700"><strong>Fecha Factura:</strong> {{ $purchase->supplier_invoice_date ? $purchase->supplier_invoice_date->format('d/m/Y') : 'N/A' }}</p>
                <p class="text-slate-700"><strong>Moneda:</strong> {{ $purchase->currency ?? 'USD' }} ($)</p>
            </div>

            <div class="space-y-1 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-4">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">TRAZABILIDAD Y REGISTRO</h3>
                @if($purchase->purchaseOrder)
                    <p class="text-slate-700"><strong>Orden de Compra:</strong> {{ $purchase->purchaseOrder->purchase_order_code }}</p>
                @endif
                <p class="text-slate-700"><strong>Registrado por:</strong> {{ $purchase->user->username ?? 'Sistema' }}</p>
                @if($purchase->notes)
                    <p class="text-slate-600 italic mt-1">"{{ $purchase->notes }}"</p>
                @endif
            </div>
        </div>

        <!-- Tabla de Productos Recibidos -->
        <div class="space-y-2">
            <h3 class="font-extrabold text-slate-800 text-sm">Detalle de Mercancía Recibida</h3>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Producto / Código</th>
                            <th class="py-2.5 px-3 text-center">Cant. Pedida</th>
                            <th class="py-2.5 px-3 text-center">Cant. Recibida</th>
                            <th class="py-2.5 px-3 text-center">Unidad</th>
                            <th class="py-2.5 px-3 text-right">P. Unitario</th>
                            <th class="py-2.5 px-3 text-right">Descuento</th>
                            <th class="py-2.5 px-3 text-right">% IVA</th>
                            <th class="py-2.5 px-3 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        @foreach($purchase->details as $index => $item)
                            <tr>
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-900 block">{{ $item->product->name ?? 'Producto' }}</span>
                                    @if($item->product?->sku || $item->product?->code)
                                        <span class="text-[10px] font-mono text-slate-400">SKU: {{ $item->product->sku ?: $item->product->code }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono text-slate-500">{{ number_format($item->quantity_ordered, 2) }}</td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold text-emerald-700">{{ number_format($item->quantity_received, 2) }}</td>
                                <td class="py-2.5 px-3 text-center text-slate-600">{{ $item->unit->name ?? 'Unidad' }}</td>
                                <td class="py-2.5 px-3 text-right font-mono">${{ number_format($item->unit_price, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-rose-600">-${{ number_format($item->discount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono">${{ number_format($item->tax_amount, 2) }} ({{ number_format($item->tax_rate, 0) }}%)</td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900">${{ number_format($item->total, 2) }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totales y Resumen Financiero -->
        <div class="flex justify-end pt-2">
            <div class="w-full sm:w-72 bg-slate-50 p-4 rounded-2xl border border-slate-200 text-xs space-y-1.5">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal Mercancía:</span>
                    <span class="font-mono font-bold">${{ number_format($purchase->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Descuentos:</span>
                    <span class="font-mono font-bold text-rose-600">-${{ number_format($purchase->discount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>Impuestos (IVA):</span>
                    <span class="font-mono font-bold">${{ number_format($purchase->tax, 2) }}</span>
                </div>
                <div class="flex justify-between text-sm font-black text-[#005e66] pt-2 border-t border-slate-200">
                    <span>Total Factura:</span>
                    <span class="font-mono text-base">${{ number_format($purchase->total, 2) }} {{ $purchase->currency }}</span>
                </div>
            </div>
        </div>

        <!-- Firmas -->
        <div class="pt-8 border-t border-slate-200">
            <div class="grid grid-cols-2 gap-12 text-center text-xs">
                <div class="space-y-1">
                    <div class="border-b border-slate-400 pb-12"></div>
                    <p class="font-extrabold text-slate-800 mt-2">{{ $purchase->user->username ?? 'Encargado de Bodega' }}</p>
                    <p class="text-slate-400 font-semibold text-[10px] uppercase">Recibido en Bodega / Almacén</p>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-slate-400 pb-12"></div>
                    <p class="font-extrabold text-slate-800 mt-2">Dpto. de Contabilidad / Cuentas por Pagar</p>
                    <p class="text-slate-400 font-semibold text-[10px] uppercase">Revisado y Contabilizado</p>
                </div>
            </div>
        </div>

        <!-- Pie de Página -->
        <div class="text-[10px] text-slate-400 text-center border-t border-slate-100 pt-3">
            Comprobante interno de recepción y compra mercantil. Generado el {{ now()->format('d/m/Y H:i:s') }}.
        </div>

    </div>

</body>
</html>
