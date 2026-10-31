<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Etiquetas {{ $lot->lot_code }}</title>
    <!-- Estilos del build (sin depender de internet para imprimir) -->
    @vite(['resources/css/app.css'])
    <style>
        .etiqueta { break-inside: avoid; page-break-inside: avoid; }
        /* El código de barras se escala al ancho de la etiqueta sin deformarse */
        .barras svg { width: 100%; height: 44px; }
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            @page { size: letter portrait; margin: 8mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6">
    <div class="no-print max-w-5xl mx-auto mb-6 flex items-center justify-between gap-4">
        <div class="text-sm">
            <p class="font-extrabold">{{ $print->quantity_printed }} etiqueta(s) del lote {{ $lot->lot_code }}</p>
            <p class="text-slate-500">{{ $print->reprint_reason ? 'Reimpresión: '.$print->reprint_reason : 'Primera impresión' }} · {{ $print->print_date?->format('d/m/Y h:i A') }}</p>
        </div>
        <div class="flex gap-2">
            <a href="{{ route('inventory.labels', $lot) }}" class="px-4 py-2 rounded-xl text-sm font-bold border border-slate-300">← Volver</a>
            <button type="button" onclick="window.print()" class="px-4 py-2 rounded-xl text-sm font-bold bg-[#005e66] text-white">Imprimir</button>
        </div>
    </div>

    <div class="max-w-5xl mx-auto grid grid-cols-3 gap-3">
        @for($i = 1; $i <= $print->quantity_printed; $i++)
            <div class="etiqueta bg-white border border-slate-400 rounded-lg p-3 space-y-1">
                <p class="text-[10px] font-bold uppercase text-slate-500">Producto</p>
                <p class="text-base font-mono font-black leading-tight">{{ $lot->product?->sku }}</p>
                <p class="text-[10px] font-semibold leading-tight text-slate-700">{{ $lot->product?->name }}</p>
                <div class="barras pt-1">{!! $barcodeSvg !!}</div>
                <p class="text-[10px] font-mono text-center tracking-widest">{{ $lot->product?->sku }}</p>
                <div class="flex justify-between gap-2 border-t border-dashed border-slate-300 pt-1 text-[10px] text-slate-700">
                    <span><span class="block text-slate-500">Lote</span><span class="font-mono font-bold">{{ $lot->lot_code }}</span></span>
                    <span class="text-right"><span class="block text-slate-500">Fecha de recepción</span><span class="font-bold">{{ $lot->received_at?->format('d/m/Y') }}</span></span>
                </div>
                @if($lot->supplier_lot_number || $lot->expiration_date)
                    <div class="flex justify-between gap-2 text-[10px] text-slate-700">
                        <span>{{ $lot->supplier_lot_number ? 'Lote prov.: '.$lot->supplier_lot_number : '' }}</span>
                        <span class="font-bold">{{ $lot->expiration_date ? 'Vence: '.$lot->expiration_date->format('d/m/Y') : '' }}</span>
                    </div>
                @endif
                <p class="text-[9px] text-slate-400 text-right">{{ $i }}/{{ $print->quantity_printed }}</p>
                @if($print->reprint_reason)
                    <p class="text-[9px] font-bold uppercase text-slate-500 text-center">Reimpresión</p>
                @endif
            </div>
        @endfor
    </div>
</body>
</html>
