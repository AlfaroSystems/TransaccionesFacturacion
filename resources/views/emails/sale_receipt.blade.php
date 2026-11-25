<x-mail::message>
# Comprobante de venta {{ $sale->sale_code }}

Estimado/a {{ $sale->customer->name ?? 'cliente' }},

Adjuntamos el comprobante de su compra realizada el {{ $sale->sale_date?->format('d/m/Y H:i') }}.

**Documento:** {{ $sale->documentLabel() }}
**Forma de pago:** {{ $sale->paymentLabel() }}
**Total:** $ {{ number_format($sale->total, 2) }}

Gracias por su compra.

<x-mail::subcopy>
El detalle completo está en el PDF adjunto.
</x-mail::subcopy>

Saludos,<br>
{{ $sale->branch->company->name ?? config('app.name') }}
</x-mail::message>
