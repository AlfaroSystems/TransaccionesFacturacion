{{-- Estado de una solicitud de compra; $purchaseRequest --}}
@php
    $statusStyles = [
        'draft' => 'bg-slate-100 text-slate-600',
        'sent' => 'bg-sky-100 text-sky-700',
        'returned' => 'bg-amber-100 text-amber-700',
        'rejected' => 'bg-rose-100 text-rose-700',
        'quoted' => 'bg-emerald-100 text-emerald-700',
    ];
@endphp
<span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-bold {{ $statusStyles[$purchaseRequest->status] ?? 'bg-slate-100 text-slate-600' }}">● {{ $purchaseRequest->statusLabel() }}</span>
