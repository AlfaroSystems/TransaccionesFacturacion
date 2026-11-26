<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Ticket {{ $sale->sale_code }}</title>
    <style>
        * { font-family: 'Courier New', monospace; }
        body { width: 80mm; margin: 0 auto; padding: 6px 8px; color: #000; font-size: 12px; }
        .c { text-align: center; }
        .b { font-weight: bold; }
        .big { font-size: 14px; }
        .muted { color: #333; font-size: 11px; }
        hr { border: none; border-top: 1px dashed #000; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; }
        td { vertical-align: top; }
        .r { text-align: right; }
        .line td { padding: 1px 0; }
        .tot td { padding: 1px 0; }
        .grand { font-size: 14px; font-weight: bold; }
        .no-print { margin-top: 10px; text-align: center; }
        @media print { .no-print { display: none; } @page { margin: 4mm; } }
    </style>
</head>
<body onload="setTimeout(function(){ window.print(); }, 300);">
    <div class="c b big">{{ $company->name ?? $company->commercial_name ?? 'Mini ERP Comercial' }}</div>
    @if($company)
        <div class="c muted">
            @if($company->nit)NIT: {{ $company->nit }}@endif @if($company->nrc) · NRC: {{ $company->nrc }}@endif
        </div>
        <div class="c muted">{{ $company->address ?? '' }}</div>
    @endif
    <hr>
    <div class="c b">{{ $sale->documentLabel() }}</div>
    <div>No.: {{ $sale->sale_code }}@if($sale->fiscal_document_number) / {{ $sale->fiscal_document_number }}@endif</div>
    <div>Fecha: {{ $sale->sale_date?->format('d/m/Y H:i') }}</div>
    <div>Cajero: {{ $sale->user->name ?? '—' }}</div>
    <div>Cliente: {{ $sale->receptor_business_name ?? $sale->customer->name ?? 'Consumidor final' }}</div>
    <hr>
    <table>
        @foreach($sale->details as $detail)
            <tr class="line"><td colspan="2">{{ $detail->product->name ?? '-' }}@if($detail->presentation) ({{ $detail->presentation->name }})@endif</td></tr>
            <tr class="line">
                <td class="muted">{{ rtrim(rtrim(number_format($detail->quantity, 4), '0'), '.') }} x {{ number_format($detail->unit_price, 2) }}@if($detail->discount > 0) - {{ number_format($detail->discount, 2) }}@endif</td>
                <td class="r">{{ number_format($detail->total, 2) }}</td>
            </tr>
        @endforeach
    </table>
    <hr>
    <table>
        <tr class="tot"><td>Subtotal</td><td class="r">$ {{ number_format($sale->subtotal, 2) }}</td></tr>
        <tr class="tot"><td>IVA</td><td class="r">$ {{ number_format($sale->tax, 2) }}</td></tr>
        <tr class="tot grand"><td>TOTAL</td><td class="r">$ {{ number_format($sale->total, 2) }}</td></tr>
    </table>
    <hr>
    <div>Pago: {{ $sale->paymentLabel() }}@if($sale->payment_reference) ({{ $sale->payment_reference }})@endif</div>
    <hr>
    <div class="c muted">¡Gracias por su compra!</div>

    <div class="no-print">
        <button type="button" onclick="window.print()">Imprimir</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>
</body>
</html>
