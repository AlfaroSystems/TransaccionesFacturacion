<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Lista de preparación {{ $transfer->transfer_code }}</title>
    <!-- Estilos del build (sin depender de internet para imprimir) -->
    @vite(['resources/css/app.css'])
    <style>
        @media print {
            .no-print { display: none !important; }
            body { background: #ffffff !important; padding: 0 !important; }
            @page { size: letter portrait; margin: 10mm; }
        }
    </style>
</head>
<body class="bg-slate-100 text-slate-900 p-6">
@php
    $qty = fn ($value) => rtrim(rtrim(number_format((float) $value, 4, '.', ''), '0'), '.');
    // En el orden en que se recorre la bodega: por ubicación
    $lineas = $transfer->details->sortBy(fn ($d) => [$d->sourceLocation?->code, $d->product?->name])->values();
@endphp
    <div class="no-print max-w-4xl mx-auto mb-6 flex items-center justify-between gap-4">
        <a href="{{ route('inventory-transfers.show', $transfer) }}" class="text-sm font-bold text-slate-500 hover:text-slate-700">← Volver al traslado</a>
        <button type="button" onclick="window.print()" class="bg-slate-800 text-white font-bold py-2 px-5 rounded-xl text-sm">Imprimir</button>
    </div>

    <div class="max-w-4xl mx-auto bg-white p-8 rounded-2xl shadow-sm print:shadow-none print:rounded-none print:p-0">
        <div class="flex items-start justify-between gap-6 border-b border-slate-300 pb-4">
            <div>
                <h1 class="text-2xl font-extrabold">Lista de preparación</h1>
                <p class="text-sm text-slate-600">Traslado <strong class="font-mono">{{ $transfer->transfer_code }}</strong> · {{ $transfer->transfer_date?->format('d/m/Y') }}</p>
            </div>
            <div class="text-right text-sm">
                <p><strong>{{ $transfer->sourceWarehouse?->name }}</strong> → <strong>{{ $transfer->destinationWarehouse?->name }}</strong></p>
                <p class="text-slate-600">{{ $transfer->destinationWarehouse?->branch?->name }}</p>
            </div>
        </div>

        <div class="grid grid-cols-3 gap-4 py-4 text-sm border-b border-slate-300">
            <p><span class="text-slate-500 block text-xs uppercase font-bold">Vehículo</span>{{ $transfer->vehicle?->label() ?? 'Sin asignar' }}</p>
            <p><span class="text-slate-500 block text-xs uppercase font-bold">Motorista</span>{{ $transfer->driver?->fullName() ?? 'Sin asignar' }}</p>
            <p><span class="text-slate-500 block text-xs uppercase font-bold">Autorizado</span>{{ $transfer->authorized_at?->format('d/m/Y h:i A') }} · {{ $transfer->authorizedBy?->username }}</p>
        </div>

        <table class="w-full text-left text-sm mt-4">
            <thead class="text-xs uppercase font-extrabold text-slate-500 border-b border-slate-300">
                <tr>
                    <th class="py-2 pr-3">Ubicación</th>
                    <th class="py-2 pr-3">Producto</th>
                    <th class="py-2 pr-3">Lote</th>
                    <th class="py-2 pr-3 text-right">Solicitado</th>
                    <th class="py-2 w-32 text-center">Preparado</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-slate-200">
                @foreach($lineas as $detalle)
                    <tr>
                        <td class="py-2.5 pr-3 font-mono font-bold">{{ $detalle->sourceLocation?->code }}</td>
                        <td class="py-2.5 pr-3">
                            <span class="font-bold block">{{ $detalle->product?->name }}</span>
                            <span class="text-xs font-mono text-slate-500">{{ $detalle->product?->sku }}</span>
                        </td>
                        <td class="py-2.5 pr-3 font-mono text-xs">
                            {{ $detalle->lot?->lot_code }}
                            @if($detalle->lot?->expiration_date)
                                <span class="block text-slate-500">Vence {{ $detalle->lot->expiration_date->format('d/m/Y') }}</span>
                            @endif
                        </td>
                        <td class="py-2.5 pr-3 text-right font-mono font-bold">{{ $qty($detalle->quantity_requested) }} <span class="text-xs font-sans text-slate-500">{{ $detalle->product?->purchaseUnit?->name }}</span></td>
                        <td class="py-2.5"><div class="border-b border-slate-400 h-6 mx-2"></div></td>
                    </tr>
                @endforeach
            </tbody>
        </table>

        <div class="grid grid-cols-3 gap-8 mt-16 text-xs text-center text-slate-600">
            <p class="border-t border-slate-400 pt-2">Preparó</p>
            <p class="border-t border-slate-400 pt-2">Entregó (bodega)</p>
            <p class="border-t border-slate-400 pt-2">Recibió (motorista)</p>
        </div>
    </div>
</body>
</html>
