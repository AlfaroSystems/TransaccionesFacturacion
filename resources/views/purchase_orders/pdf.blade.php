<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Orden de Compra {{ $purchase_order->purchase_order_code ?? 'OC-' . $purchase_order->id_purchase_order }}</title>
    <!-- Tailwind CSS CDN para renderizado perfecto de impresión -->
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @media print {
            .no-print {
                display: none !important;
            }
            body {
                background-color: #ffffff !important;
                color: #000000 !important;
                font-size: 12px !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            @page {
                size: letter portrait;
                margin: 15mm;
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans p-4 md:p-8" onload="setTimeout(() => { window.print(); }, 500);">

    <!-- Barra de Acciones Superior (No Imprimible) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-teal-100 text-[#005e66] font-extrabold text-xs rounded-lg uppercase tracking-wider">
                Documento Oficial
            </span>
            <span class="text-sm font-bold text-slate-700">Orden de Compra: {{ $purchase_order->purchase_order_code }}</span>
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

    <!-- Documento de Orden de Compra Imprimible -->
    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-3xl shadow-lg border border-slate-200 print-card space-y-8">
        
        <!-- Encabezado / Logo / Datos de la Empresa -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-200 pb-6 gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#005e66] text-white flex items-center justify-center font-extrabold text-xl">
                        ERP
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-[#005e66] tracking-tight">SISTEMA DE TRANSACCIONES & FACTURACIÓN</h1>
                        <p class="text-xs text-slate-500 font-semibold">Documento Comercial de Adquisiciones</p>
                    </div>
                </div>
                <div class="mt-4 text-xs text-slate-600 space-y-1">
                    <p><strong>Sucursal Emisora:</strong> {{ $purchase_order->branch->name ?? 'Sucursal Central' }}</p>
                    <p><strong>Bodega Receptor:</strong> {{ $purchase_order->warehouse->name ?? 'Bodega Principal' }}</p>
                    <p><strong>Usuario Emisor:</strong> {{ $purchase_order->user->name ?? 'Sistema' }} ({{ $purchase_order->user->email ?? 'N/A' }})</p>
                </div>
            </div>

            <div class="text-left sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto">
                <span class="px-3 py-1 text-xs font-extrabold uppercase rounded-lg border border-teal-300 bg-teal-50 text-[#005e66]">
                    ORDEN DE COMPRA
                </span>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight mt-2">
                    {{ $purchase_order->purchase_order_code ?? 'OC-' . $purchase_order->id_purchase_order }}
                </h2>
                <div class="text-xs text-slate-500 font-semibold mt-2 space-y-1">
                    <p>Fecha Emisión: <strong class="text-slate-800">{{ $purchase_order->order_date ? $purchase_order->order_date->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</strong></p>
                    <p>Fecha Esperada: <strong class="text-slate-800">{{ $purchase_order->expected_date ? $purchase_order->expected_date->format('d/m/Y') : 'N/A' }}</strong></p>
                    <p>Estado: <strong class="uppercase text-[#005e66]">{{ $purchase_order->status }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Información del Proveedor y Condiciones -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-6 bg-slate-50 p-5 rounded-2xl border border-slate-200/80 text-xs">
            <div class="space-y-1.5">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[11px] mb-2">DATOS DEL PROVEEDOR</h3>
                <p class="text-sm font-extrabold text-slate-900">{{ $purchase_order->supplier->name ?? 'N/A' }}</p>
                @if($purchase_order->supplier?->nit || $purchase_order->supplier?->nrc)
                    <p class="text-slate-600"><strong>NIT/NRC:</strong> {{ $purchase_order->supplier->nit ?? '' }} / {{ $purchase_order->supplier->nrc ?? '' }}</p>
                @endif
                <p class="text-slate-600"><strong>Teléfono:</strong> {{ $purchase_order->supplier->phone ?? 'N/A' }}</p>
                <p class="text-slate-600"><strong>Email:</strong> {{ $purchase_order->supplier->email ?? 'N/A' }}</p>
                @if($purchase_order->supplier?->address)
                    <p class="text-slate-600"><strong>Dirección:</strong> {{ $purchase_order->supplier->address }}</p>
                @endif
            </div>

            <div class="space-y-1.5 border-t sm:border-t-0 sm:border-l border-slate-200 pt-4 sm:pt-0 sm:pl-6">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[11px] mb-2">CONDICIONES DE LA ORDEN</h3>
                <p class="text-slate-700"><strong>Condiciones de Pago:</strong> {{ $purchase_order->payment_terms ?? 'Contrapago' }}</p>
                <p class="text-slate-700"><strong>Moneda de Transacción:</strong> {{ $purchase_order->currency ?? 'USD' }} ($)</p>
                @if($purchase_order->quotation)
                    <p class="text-slate-700"><strong>Cotización de Origen:</strong> {{ $purchase_order->quotation->purchase_quotation_code }}</p>
                @endif
                @if($purchase_order->notes)
                    <p class="text-slate-700"><strong>Observaciones:</strong> {{ $purchase_order->notes }}</p>
                @endif
            </div>
        </div>

        <!-- Tabla de Productos Cotizados/Ordenados -->
        <div class="space-y-3">
            <h3 class="font-extrabold text-slate-800 text-sm">Detalle de Productos Solicitados</h3>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4 text-center">Cantidad</th>
                            <th class="py-3 px-4 text-center">Unidad</th>
                            <th class="py-3 px-4 text-right">P. Unitario</th>
                            <th class="py-3 px-4 text-right">Desc.</th>
                            <th class="py-3 px-4 text-right">Impuesto</th>
                            <th class="py-3 px-4 text-right">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        @forelse($purchase_order->details as $index => $detail)
                            <tr>
                                <td class="py-3 px-4 font-mono font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $detail->product->name ?? 'Producto' }}
                                    @if($detail->product?->sku)
                                        <span class="block text-[10px] font-mono text-slate-400 font-normal">SKU: {{ $detail->product->sku }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold">{{ number_format($detail->quantity, 2) }}</td>
                                <td class="py-3 px-4 text-center">{{ $detail->unit->name ?? 'Pieza' }}</td>
                                <td class="py-3 px-4 text-right font-mono">${{ number_format($detail->unit_price, 2) }}</td>
                                <td class="py-3 px-4 text-right font-mono text-rose-600">${{ number_format($detail->discount, 2) }}</td>
                                <td class="py-3 px-4 text-right font-mono">${{ number_format($detail->tax_amount, 2) }} ({{ number_format($detail->tax_rate, 0) }}%)</td>
                                <td class="py-3 px-4 text-right font-mono font-extrabold text-slate-900">${{ number_format($detail->total, 2) }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="8" class="py-6 text-center text-slate-400">No hay productos registrados en esta orden.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Gastos Adicionales (Si Aplica) -->
        @if($purchase_order->expenses && count($purchase_order->expenses) > 0)
            <div class="space-y-2">
                <h3 class="font-extrabold text-slate-800 text-xs uppercase tracking-wider">Gastos Adicionales Asignados</h3>
                <div class="border border-slate-200 rounded-xl overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-50 text-slate-600 font-bold uppercase border-b border-slate-200">
                            <tr>
                                <th class="py-2 px-4">Tipo de Gasto</th>
                                <th class="py-2 px-4">Descripción / Concepto</th>
                                <th class="py-2 px-4 text-right">Monto ($)</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 font-medium text-slate-700">
                            @foreach($purchase_order->expenses as $expense)
                                <tr>
                                    <td class="py-2 px-4 font-bold text-slate-800">{{ $expense->expenseType->name ?? 'Gasto' }}</td>
                                    <td class="py-2 px-4 text-slate-500">{{ $expense->description ?? '-' }}</td>
                                    <td class="py-2 px-4 text-right font-mono font-bold text-slate-900">${{ number_format($expense->amount, 2) }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <!-- Resumen de Totales Consolidados -->
        <div class="flex flex-col sm:flex-row justify-between items-start pt-4 border-t border-slate-200 gap-6">
            <div class="text-xs text-slate-500 space-y-1 max-w-sm">
                <p class="font-bold text-slate-700 mb-1">Notas de Emisión:</p>
                <p>Este documento es una representación impresa de la Orden de Compra emitida formalmente por el Sistema ERP de Transacciones y Facturación.</p>
            </div>

            <div class="w-full sm:w-72 bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-2 text-xs">
                <div class="flex justify-between text-slate-600">
                    <span>Subtotal:</span>
                    <span class="font-mono font-bold text-slate-800">${{ number_format($purchase_order->subtotal, 2) }}</span>
                </div>
                <div class="flex justify-between text-rose-600">
                    <span>Descuento:</span>
                    <span class="font-mono font-bold">-${{ number_format($purchase_order->discount, 2) }}</span>
                </div>
                <div class="flex justify-between text-slate-600">
                    <span>IVA (Impuestos):</span>
                    <span class="font-mono font-bold text-slate-800">${{ number_format($purchase_order->tax, 2) }}</span>
                </div>
                <div class="flex justify-between text-amber-700">
                    <span>Gastos Adicionales:</span>
                    <span class="font-mono font-bold">+${{ number_format($purchase_order->additional_expenses, 2) }}</span>
                </div>
                <div class="border-t border-slate-300 pt-2 flex justify-between items-center text-sm font-black text-[#005e66]">
                    <span>TOTAL GENERAL:</span>
                    <span class="font-mono text-base">${{ number_format($purchase_order->total, 2) }} {{ $purchase_order->currency ?? 'USD' }}</span>
                </div>
            </div>
        </div>

        <!-- Secciones de Firmas y Conformidad -->
        <div class="pt-12 grid grid-cols-2 gap-12 text-center text-xs">
            <div class="border-t border-slate-300 pt-2 space-y-1">
                <p class="font-bold text-slate-800">Elaborado y Emitido Por</p>
                <p class="text-slate-400 font-mono">{{ $purchase_order->user->name ?? 'Firma Autorizada' }}</p>
            </div>
            <div class="border-t border-slate-300 pt-2 space-y-1">
                <p class="font-bold text-slate-800">Aceptado y Recibido Por (Proveedor)</p>
                <p class="text-slate-400 font-mono">{{ $purchase_order->supplier->name ?? 'Firma / Sello Proveedor' }}</p>
            </div>
        </div>

    </div>

</body>
</html>
