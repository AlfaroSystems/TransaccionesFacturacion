{{-- Estado de una solicitud de compra; $purchaseRequest --}}
@php
    $tone = [
        'draft' => 'neutral',
        'sent' => 'info',
        'returned' => 'warning',
        'rejected' => 'danger',
        'approved' => 'success',
        'quoted' => 'primary',
    ][$purchaseRequest->status] ?? 'neutral';
@endphp
<x-ui.badge :tone="$tone">{{ $purchaseRequest->statusLabel() }}</x-ui.badge>
