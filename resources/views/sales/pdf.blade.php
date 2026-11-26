<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Comprobante {{ $sale->sale_code }}</title>
    <style>
        * { font-family: DejaVu Sans, sans-serif; }
        body { font-size: 11px; color: #1f2937; margin: 0; }
        .muted { color: #6b7280; }
        .teal { color: #005e66; }
        h1 { font-size: 16px; margin: 0; color: #005e66; }
        table { width: 100%; border-collapse: collapse; }
        .header td { vertical-align: top; }
        .doc-title { text-align: right; }
        .doc-title .badge { background: #005e66; color: #fff; padding: 4px 10px; border-radius: 4px; font-weight: bold; font-size: 11px; }
        .box { border: 1px solid #e5e7eb; border-radius: 6px; padding: 10px; margin-top: 12px; }
        .items th { background: #f1f5f9; text-align: left; padding: 6px 8px; font-size: 10px; text-transform: uppercase; color: #64748b; }
        .items td { padding: 6px 8px; border-bottom: 1px solid #eef2f7; }
        .right { text-align: right; }
        .center { text-align: center; }
        .totals td { padding: 3px 8px; }
        .totals .grand { font-size: 13px; font-weight: bold; color: #005e66; border-top: 1px solid #e5e7eb; }
        .label { font-size: 9px; text-transform: uppercase; color: #94a3b8; font-weight: bold; }
    </style>
</head>
<body>
    @php
        $fiscal = $sale->document_type === 'ccf';
    @endphp
    <table class="header">
        <tr>
            <td style="width: 60%;">
                <h1>{{ $company->name ?? $company->commercial_name ?? 'Mini ERP Comercial' }}</h1>
                @if($company)
                    <div class="muted">
                        @if($company->nit){{ 'NIT: '.$company->nit }}@endif
                        @if($company->nrc) &nbsp; {{ 'NRC: '.$company->nrc }}@endif
                    </div>
                    <div class="muted">{{ $company->address ?? '' }}</div>
                    <div class="muted">{{ $company->phone ?? '' }} {{ $company->email ? ' · '.$company->email : '' }}</div>
                @endif
            </td>
            <td class="doc-title" style="width: 40%;">
                <span class="badge">{{ $sale->documentLabel() }}</span>
                <div style="margin-top: 8px;"><span class="label">Comprobante</span><br><strong>{{ $sale->sale_code }}</strong></div>
                @if($sale->fiscal_document_number)
                    <div><span class="label">No. documento</span><br>{{ $sale->fiscal_document_number }}</div>
                @endif
                <div style="margin-top: 4px;"><span class="label">Fecha</span><br>{{ $sale->sale_date?->format('d/m/Y H:i') }}</div>
            </td>
        </tr>
    </table>

    <table class="box">
        <tr>
            <td style="width: 50%;">
                @php
                    $docNum = $sale->receptor_document_number ?? $sale->customer?->document_number;
                    $docType = $sale->receptor_document_type ?? $sale->customer?->document_type;
                @endphp
                <span class="label">Cliente</span><br>
                <strong>{{ $sale->receptor_business_name ?? $sale->customer->name ?? '-' }}</strong><br>
                @if($docNum)<span class="muted">{{ strtoupper($docType ?? '') }} {{ $docNum }}</span><br>@endif
                @if($fiscal)
                    @if($sale->receptor_nrc)<span class="muted">NRC: {{ $sale->receptor_nrc }}</span><br>@endif
                    @if($sale->receptor_line)<span class="muted">Giro: {{ $sale->receptor_line }}</span><br>@endif
                    @if($sale->receptor_address)<span class="muted">{{ $sale->receptor_address }}</span>@endif
                @endif
            </td>
            <td style="width: 50%;">
                <span class="label">Sucursal</span> {{ $sale->branch->name ?? '-' }}<br>
                <span class="label">Forma de pago</span> {{ $sale->paymentLabel() }}@if($sale->payment_reference) <span class="muted">(Ref: {{ $sale->payment_reference }}@if($sale->payment_bank) · {{ $sale->payment_bank }}@endif)</span>@endif<br>
                <span class="label">Vendedor</span> {{ $sale->user->name ?? '-' }}
            </td>
        </tr>
    </table>

    <table class="items" style="margin-top: 14px;">
        <thead>
            <tr>
                <th>Producto</th>
                <th class="center" style="width: 60px;">Cant.</th>
                <th class="right" style="width: 80px;">Precio</th>
                <th class="right" style="width: 70px;">Desc.</th>
                <th class="right" style="width: 70px;">IVA</th>
                <th class="right" style="width: 85px;">Total</th>
            </tr>
        </thead>
        <tbody>
            @foreach($sale->details as $detail)
                <tr>
                    <td>{{ $detail->product->name ?? '-' }}@if($detail->presentation) ({{ $detail->presentation->name }})@endif</td>
                    <td class="center">{{ rtrim(rtrim(number_format($detail->quantity, 4), '0'), '.') }} {{ $detail->unit->name ?? '' }}</td>
                    <td class="right">{{ number_format($detail->unit_price, 2) }}</td>
                    <td class="right">{{ number_format($detail->discount, 2) }}</td>
                    <td class="right">{{ number_format($detail->tax_amount, 2) }}</td>
                    <td class="right">{{ number_format($detail->total, 2) }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <table style="margin-top: 14px;">
        <tr>
            <td style="width: 60%;"></td>
            <td style="width: 40%;">
                <table class="totals">
                    <tr><td class="muted">Subtotal</td><td class="right">$ {{ number_format($sale->subtotal, 2) }}</td></tr>
                    <tr><td class="muted">Descuento</td><td class="right">$ {{ number_format($sale->discount, 2) }}</td></tr>
                    <tr><td class="muted">IVA</td><td class="right">$ {{ number_format($sale->tax, 2) }}</td></tr>
                    <tr><td class="grand">Total</td><td class="right grand">$ {{ number_format($sale->total, 2) }}</td></tr>
                </table>
            </td>
        </tr>
    </table>

    <p class="muted" style="margin-top: 24px; font-size: 10px;">Documento generado por el Mini ERP Comercial. Gracias por su compra.</p>
</body>
</html>
