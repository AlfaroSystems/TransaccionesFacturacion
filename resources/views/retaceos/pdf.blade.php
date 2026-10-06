<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Liquidación de Retaceo {{ $retaceo->retaceo_code ?? 'RET-' . $retaceo->id_retaceo }}</title>
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
                Documento Oficial de Liquidación
            </span>
            <span class="text-sm font-bold text-slate-700">Retaceo: {{ $retaceo->retaceo_code }}</span>
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

    <!-- Documento de Retaceo Imprimible -->
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
                            {{ $company->commercial_name ?? 'Liquidación de Costos de Importación & Aduana' }}
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
                        <strong>Sucursal:</strong> {{ $retaceo->purchase?->branch?->name ?? 'Sucursal Central' }} 
                        &nbsp;|&nbsp; 
                        <strong>Bodega de Ingreso:</strong> {{ $retaceo->purchase?->warehouse?->name ?? 'Bodega Principal' }}
                    </p>
                </div>
            </div>

            <div class="text-left sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto">
                <span class="px-3 py-1 text-xs font-extrabold uppercase rounded-lg border border-teal-300 bg-teal-50 text-[#005e66]">
                    LIQUIDACIÓN DE RETACEO
                </span>
                <h2 class="text-2xl font-black text-slate-900 tracking-tight mt-2 font-mono">
                    {{ $retaceo->retaceo_code }}
                </h2>
                <div class="text-xs text-slate-500 font-semibold mt-2 space-y-1">
                    <p>Fecha Liquidación: <strong class="text-slate-800">{{ $retaceo->retaceo_date ? $retaceo->retaceo_date->format('d/m/Y H:i') : now()->format('d/m/Y H:i') }}</strong></p>
                    @if($retaceo->import_policy_number)
                        <p>Póliza / DM Aduana: <strong class="text-[#005e66]">{{ $retaceo->import_policy_number }}</strong></p>
                    @endif
                    @php
                        $statusLabels = [
                            'draft' => 'Borrador',
                            'calculated' => 'Liquidado / Calculado',
                            'applied' => 'Aplicado a Inventario',
                            'cancelled' => 'Cancelado'
                        ];
                    @endphp
                    <p>Estado: <strong class="uppercase text-emerald-700 font-bold">{{ $statusLabels[$retaceo->status] ?? ucfirst($retaceo->status) }}</strong></p>
                </div>
            </div>
        </div>

        <!-- Información de Proveedor, Compra y Aduana -->
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 bg-slate-50 p-4 rounded-2xl border border-slate-200/80 text-xs">
            <!-- Proveedor Exterior -->
            <div class="space-y-1">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">PROVEEDOR EXTERIOR</h3>
                <p class="font-bold text-slate-900 text-sm">{{ $retaceo->supplier->name ?? ($retaceo->purchase?->supplier?->name ?? 'N/A') }}</p>
                <p class="text-slate-600"><strong>País de Origen:</strong> {{ $retaceo->origin_country ?? ($retaceo->supplier?->country ?? 'N/A') }}</p>
                @if($retaceo->supplier?->email)
                    <p class="text-slate-600"><strong>Email:</strong> {{ $retaceo->supplier->email }}</p>
                @endif
            </div>

            <!-- Factura y Póliza -->
            <div class="space-y-1 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-4">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">DOCUMENTOS ADUANALES</h3>
                <p class="text-slate-700"><strong>Factura Exterior:</strong> {{ $retaceo->import_invoice_number ?: ($retaceo->purchase?->supplier_invoice_number ?: 'N/A') }}</p>
                <p class="text-slate-700"><strong>Fecha Factura:</strong> {{ $retaceo->import_invoice_date ? $retaceo->import_invoice_date->format('d/m/Y') : ($retaceo->purchase?->supplier_invoice_date ? $retaceo->purchase->supplier_invoice_date->format('d/m/Y') : 'N/A') }}</p>
                <p class="text-slate-700"><strong>Póliza / DM Aduana:</strong> {{ $retaceo->import_policy_number ?: 'N/A' }}</p>
                <p class="text-slate-700"><strong>Fecha Póliza:</strong> {{ $retaceo->import_policy_date ? $retaceo->import_policy_date->format('d/m/Y') : 'N/A' }}</p>
            </div>

            <!-- Referencias Internas -->
            <div class="space-y-1 border-t sm:border-t-0 sm:border-l border-slate-200 pt-3 sm:pt-0 sm:pl-4">
                <h3 class="font-extrabold text-[#005e66] uppercase tracking-wider text-[10px]">REFERENCIA DE COMPRA</h3>
                <p class="text-slate-700"><strong>Factura Compra:</strong> {{ $retaceo->purchase?->purchase_code ?? 'N/A' }}</p>
                @if($retaceo->purchase?->purchaseOrder)
                    <p class="text-slate-700"><strong>Orden Compra:</strong> {{ $retaceo->purchase->purchaseOrder->purchase_order_code }}</p>
                @endif
                <p class="text-slate-700"><strong>Liquidador:</strong> {{ $retaceo->user->username ?? 'Administración' }}</p>
                @if($retaceo->notes)
                    <p class="text-slate-600 italic mt-1">"{{ $retaceo->notes }}"</p>
                @endif
            </div>
        </div>

        <!-- Resumen de Costos y Prorrateo -->
        @php
            $totalFob = (float) $retaceo->details->sum('cost_fob');
            $totalFreight = (float) $retaceo->total_freight;
            $totalExpenses = (float) $retaceo->total_expenses;
            $totalDai = (float) $retaceo->total_dai;
            $totalCost = (float) $retaceo->total_cost;
            $factor = $totalFob > 0 ? (($totalCost - $totalFob) / $totalFob) * 100 : 0;
        @endphp
        <div class="grid grid-cols-2 sm:grid-cols-5 gap-3 bg-teal-50/50 p-4 rounded-2xl border border-teal-100 text-xs">
            <div class="text-center">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Total Valor FOB</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">${{ number_format($totalFob, 2) }}</span>
            </div>
            <div class="text-center">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Flete Prorrateado</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">+${{ number_format($totalFreight, 2) }}</span>
            </div>
            <div class="text-center">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Gastos / Seguro</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">+${{ number_format($totalExpenses, 2) }}</span>
            </div>
            <div class="text-center">
                <span class="block text-[10px] font-bold text-slate-400 uppercase">Arancel / DAI</span>
                <span class="text-base font-extrabold text-slate-800 font-mono">+${{ number_format($totalDai, 2) }}</span>
            </div>
            <div class="text-center col-span-2 sm:col-span-1 bg-white p-2 rounded-xl border border-teal-200 shadow-sm">
                <span class="block text-[10px] font-extrabold text-[#005e66] uppercase">Costo Total Liquidado</span>
                <span class="text-base font-black text-[#005e66] font-mono">${{ number_format($totalCost, 2) }}</span>
            </div>
        </div>

        <!-- Tabla Detallada de Prorrateo -->
        <div class="space-y-2">
            <div class="flex justify-between items-center">
                <h3 class="font-extrabold text-slate-800 text-sm">Detalle de Costeo y Distribución por Producto</h3>
                <span class="text-xs font-bold text-[#005e66] bg-teal-50 px-2 py-0.5 rounded border border-teal-200">
                    Factor de Incremento: +{{ number_format($factor, 2) }}%
                </span>
            </div>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-2.5 px-3">#</th>
                            <th class="py-2.5 px-3">Producto / Código</th>
                            <th class="py-2.5 px-3 text-center">Cant.</th>
                            <th class="py-2.5 px-3 text-center">Unidad</th>
                            <th class="py-2.5 px-3 text-right">Costo FOB ($)</th>
                            <th class="py-2.5 px-3 text-right">Flete ($)</th>
                            <th class="py-2.5 px-3 text-right">Gastos ($)</th>
                            <th class="py-2.5 px-3 text-right">DAI ($)</th>
                            <th class="py-2.5 px-3 text-right">Costo Total</th>
                            <th class="py-2.5 px-3 text-right bg-teal-50 text-[#005e66]">Costo Unit. Real</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        @foreach($retaceo->details as $index => $detail)
                            @php
                                $unitName = $detail->purchaseDetail?->unit?->name ?? 'Unidad';
                            @endphp
                            <tr class="hover:bg-slate-50/50">
                                <td class="py-2.5 px-3 font-mono font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-2.5 px-3">
                                    <span class="font-bold text-slate-900 block">{{ $detail->product->name ?? 'Producto' }}</span>
                                    @if($detail->product?->sku || $detail->product?->code)
                                        <span class="text-[10px] font-mono text-slate-400">SKU: {{ $detail->product->sku ?: $detail->product->code }}</span>
                                    @endif
                                </td>
                                <td class="py-2.5 px-3 text-center font-mono font-bold">{{ number_format($detail->quantity, 2) }}</td>
                                <td class="py-2.5 px-3 text-center text-slate-600">{{ $unitName }}</td>
                                <td class="py-2.5 px-3 text-right font-mono">${{ number_format($detail->cost_fob, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">${{ number_format($detail->freight_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">${{ number_format($detail->expense_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono text-slate-600">${{ number_format($detail->dai_amount, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-bold text-slate-900">${{ number_format($detail->total_cost, 2) }}</td>
                                <td class="py-2.5 px-3 text-right font-mono font-black text-[#005e66] bg-teal-50/70 text-sm">
                                    ${{ number_format($detail->unit_cost, 4) }}
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                    <tfoot class="bg-slate-50 font-extrabold text-slate-800 border-t-2 border-slate-300">
                        <tr>
                            <td colspan="4" class="py-2.5 px-3 uppercase text-right">Totales Consolidados:</td>
                            <td class="py-2.5 px-3 text-right font-mono">${{ number_format($totalFob, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">${{ number_format($totalFreight, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">${{ number_format($totalExpenses, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono">${{ number_format($totalDai, 2) }}</td>
                            <td class="py-2.5 px-3 text-right font-mono font-black text-slate-900">${{ number_format($totalCost, 2) }}</td>
                            <td class="py-2.5 px-3 text-right bg-teal-100/70 text-[#005e66] font-black font-mono">--</td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>

        <!-- Firmas y Responsables Legales -->
        <div class="pt-10 border-t border-slate-200">
            <div class="grid grid-cols-3 gap-8 text-center text-xs">
                <div class="space-y-1">
                    <div class="border-b border-slate-400 pb-12"></div>
                    <p class="font-extrabold text-slate-800 mt-2">{{ $retaceo->user->username ?? 'Liquidador' }}</p>
                    <p class="text-slate-400 font-semibold text-[10px] uppercase">Elaborado por (Compras/Comercio Exterior)</p>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-slate-400 pb-12"></div>
                    <p class="font-extrabold text-slate-800 mt-2">Dpto. de Contabilidad</p>
                    <p class="text-slate-400 font-semibold text-[10px] uppercase">Revisado por (Costos e Impuestos)</p>
                </div>
                <div class="space-y-1">
                    <div class="border-b border-slate-400 pb-12"></div>
                    <p class="font-extrabold text-slate-800 mt-2">Gerencia General / Financiera</p>
                    <p class="text-slate-400 font-semibold text-[10px] uppercase">Aprobado y Aplicado a Inventario</p>
                </div>
            </div>
        </div>

        <!-- Pie de Página Legal -->
        <div class="text-[10px] text-slate-400 text-center border-t border-slate-100 pt-4">
            Este documento constituye la liquidación y prorrateo oficial de costos de importación para efectos de costeo de inventarios y control tributario interno. Generado electrónicamente el {{ now()->format('d/m/Y H:i:s') }}.
        </div>

    </div>

</body>
</html>
