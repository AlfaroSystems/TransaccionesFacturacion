@php
    $code = '#' . str_pad($quotationRequest->id_purchase_quotation_request, 4, '0', STR_PAD_LEFT);
@endphp
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Solicitud de Cotización {{ $code }}</title>
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
                font-size: 12px !important;
            }
            .print-card {
                border: none !important;
                box-shadow: none !important;
                padding: 0 !important;
            }
            /* Más compacto (tamaños y espacios en rem) para que quepa en una hoja */
            html {
                font-size: 14px;
            }
            .print-card > :not(:last-child) {
                margin-block-end: 1.25rem !important;
            }
            .print-tight th, .print-tight td {
                padding-top: 0.5rem !important;
                padding-bottom: 0.5rem !important;
            }
            .print-signatures {
                padding-top: 2.5rem !important;
            }
            /* Los totales y las firmas no se parten entre dos páginas, ni las filas */
            .print-keep, tr {
                break-inside: avoid;
                page-break-inside: avoid;
            }
            /* Márgenes vacíos: el navegador no imprime su encabezado y pie (fecha, título, dirección y página) */
            @page {
                size: letter portrait;
                margin: 15mm;
                @top-left { content: ""; }
                @top-center { content: ""; }
                @top-right { content: ""; }
                @bottom-left { content: ""; }
                @bottom-center { content: ""; }
                @bottom-right { content: ""; }
            }
        }
    </style>
</head>
<body class="bg-slate-100 min-h-screen text-slate-800 font-sans p-4 md:p-8" onload="setTimeout(() => { window.print(); }, 500);">

    <!-- Barra de Acciones Superior (No Imprimible) -->
    <div class="max-w-4xl mx-auto mb-6 flex items-center justify-between no-print bg-white p-4 rounded-2xl shadow-sm border border-slate-200">
        <div class="flex items-center gap-3">
            <span class="px-3 py-1 bg-teal-100 text-[#005e66] font-extrabold text-xs rounded-lg uppercase tracking-wider">
                Para Proveedores
            </span>
            <span class="text-sm font-bold text-slate-700">Solicitud de Cotización: {{ $code }} · {{ $lines->count() }} {{ $lines->count() === 1 ? 'producto' : 'productos' }}</span>
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
    <div class="max-w-4xl mx-auto bg-white p-8 md:p-12 rounded-3xl shadow-lg border border-slate-200 print-card space-y-8">

        <!-- Encabezado: empresa que solicita -->
        <div class="flex flex-col sm:flex-row justify-between items-start border-b border-slate-200 pb-6 gap-6">
            <div>
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-xl bg-[#005e66] text-white flex items-center justify-center font-extrabold text-xl">
                        ERP
                    </div>
                    <div>
                        <h1 class="text-xl font-extrabold text-[#005e66] tracking-tight">{{ $company?->commercial_name ?: ($company?->name ?? 'SISTEMA DE TRANSACCIONES & FACTURACIÓN') }}</h1>
                        @if($company?->commercial_name && $company?->name)
                            <p class="text-xs text-slate-500 font-semibold">{{ $company->name }}</p>
                        @endif
                    </div>
                </div>
                @if($company)
                    <div class="mt-4 text-xs text-slate-600 space-y-1">
                        @if($company->nit || $company->nrc)
                            <p><strong>NIT/NRC:</strong> {{ $company->nit }} / {{ $company->nrc }}</p>
                        @endif
                        @if($company->addres)
                            <p><strong>Dirección:</strong> {{ $company->addres }}</p>
                        @endif
                        @if($company->phone)
                            <p><strong>Teléfono:</strong> {{ $company->phone }}</p>
                        @endif
                        @if($company->email)
                            <p><strong>Email:</strong> {{ $company->email }}</p>
                        @endif
                    </div>
                @endif
            </div>

            <div class="text-left sm:text-right border-t sm:border-t-0 pt-4 sm:pt-0 w-full sm:w-auto">
                <span class="px-3 py-1 text-xs font-extrabold uppercase rounded-lg border border-teal-300 bg-teal-50 text-[#005e66]">
                    SOLICITUD DE COTIZACIÓN
                </span>
                <h2 class="text-3xl font-black text-slate-900 tracking-tight mt-2">{{ $code }}</h2>
                <div class="text-xs text-slate-500 font-semibold mt-2 space-y-1">
                    <p>Fecha: <strong class="text-slate-800">{{ now()->format('d/m/Y') }}</strong></p>
                </div>
            </div>
        </div>


        <p class="text-xs text-slate-600">
            Le solicitamos su mejor oferta para los productos detallados a continuación. Indique el precio unitario de cada
            producto, y deje en blanco los que no pueda ofrecer.
        </p>

        <!-- Productos a cotizar -->
        <div class="space-y-3">
            <h3 class="font-extrabold text-slate-800 text-sm">Productos a Cotizar</h3>
            <div class="border border-slate-200 rounded-xl overflow-hidden">
                <table class="print-tight w-full text-left text-xs">
                    <thead class="bg-slate-100 text-slate-700 font-extrabold uppercase border-b border-slate-200">
                        <tr>
                            <th class="py-3 px-4">#</th>
                            <th class="py-3 px-4">Producto</th>
                            <th class="py-3 px-4 text-center">Cantidad</th>
                            <th class="py-3 px-4 text-center">Unidad</th>
                            <th class="py-3 px-4 text-right w-28">P. Unitario</th>
                            <th class="py-3 px-4 text-right w-28">Total</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-slate-200 font-medium text-slate-700">
                        @foreach($lines as $index => $line)
                            <tr>
                                <td class="py-3 px-4 font-mono font-bold text-slate-400">{{ $index + 1 }}</td>
                                <td class="py-3 px-4 font-bold text-slate-800">
                                    {{ $line->product?->name ?? 'Producto' }}
                                    @if($line->product?->sku)
                                        <span class="block text-[10px] font-mono text-slate-400 font-normal">SKU: {{ $line->product->sku }}</span>
                                    @endif
                                </td>
                                <td class="py-3 px-4 text-center font-mono font-bold">{{ number_format($line->quantity, 2) }}</td>
                                <td class="py-3 px-4 text-center">{{ $line->unit?->name ?? 'Unidad' }}</td>
                                <td class="py-3 px-4"><span class="block border-b border-slate-300">&nbsp;</span></td>
                                <td class="py-3 px-4"><span class="block border-b border-slate-300">&nbsp;</span></td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Totales que completa el proveedor y firmas: van juntos, sin partirse entre páginas -->
        <div class="print-keep space-y-8">
            <div class="flex flex-col sm:flex-row justify-between items-start pt-4 border-t border-slate-200 gap-6">
                <div class="text-xs text-slate-500 space-y-1 max-w-sm">
                    <p class="font-bold text-slate-700 mb-1">Observaciones del proveedor:</p>
                    <p class="border-b border-slate-300">&nbsp;</p>
                    <p class="border-b border-slate-300">&nbsp;</p>
                </div>

                <div class="w-full sm:w-72 bg-slate-50 p-4 rounded-2xl border border-slate-200 space-y-3 text-xs">
                    <div class="flex justify-between items-end gap-3 text-slate-600">
                        <span>Subtotal:</span>
                        <span class="flex-1 border-b border-slate-300">&nbsp;</span>
                    </div>
                    <div class="flex justify-between items-end gap-3 text-slate-600">
                        <span>Descuento:</span>
                        <span class="flex-1 border-b border-slate-300">&nbsp;</span>
                    </div>
                    <div class="flex justify-between items-end gap-3 text-slate-600">
                        <span>IVA (Impuestos):</span>
                        <span class="flex-1 border-b border-slate-300">&nbsp;</span>
                    </div>
                    <div class="flex justify-between items-end gap-3 text-slate-600">
                        <span>Otros gastos (flete, etc.):</span>
                        <span class="flex-1 border-b border-slate-300">&nbsp;</span>
                    </div>
                    <div class="border-t border-slate-300 pt-2 flex justify-between items-end gap-3 text-sm font-black text-[#005e66]">
                        <span>TOTAL:</span>
                        <span class="flex-1 border-b border-slate-400">&nbsp;</span>
                    </div>
                </div>
            </div>

            <!-- Firmas -->
            <div class="print-signatures pt-12 grid grid-cols-2 gap-12 text-center text-xs">
                <div class="border-t border-slate-300 pt-2 space-y-1">
                    <p class="font-bold text-slate-800">Solicitado Por</p>
                    <p class="text-slate-400 font-mono">{{ auth()->user()?->username ?? 'Departamento de Compras' }}</p>
                </div>
                <div class="border-t border-slate-300 pt-2 space-y-1">
                    <p class="font-bold text-slate-800">Firma y Sello del Proveedor</p>
                    <p class="text-slate-400 font-mono">&nbsp;</p>
                </div>
            </div>
        </div>

    </div>

</body>
</html>
