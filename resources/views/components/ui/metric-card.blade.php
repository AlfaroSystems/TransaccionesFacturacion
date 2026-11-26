@props([
    'icon' => 'box',
    'tone' => 'primary',   // primary | info | success | warning | danger | neutral
    'label' => '',
    'value' => '',
    'href' => null,
    'linkText' => null,
])
@php
    $tiles = [
        'primary' => 'bg-primary-soft text-on-primary-soft',
        'info'    => 'bg-info-soft text-info',
        'success' => 'bg-success-soft text-success',
        'warning' => 'bg-warning-soft text-warning',
        'danger'  => 'bg-danger-soft text-danger',
        'neutral' => 'bg-sunken text-muted',
    ];
@endphp
<div {{ $attributes->merge(['class' => 'ui-surface p-5']) }}>
    <div class="flex items-center gap-4">
        <div class="w-12 h-12 rounded-2xl flex items-center justify-center shrink-0 {{ $tiles[$tone] ?? $tiles['primary'] }}">
            <x-icon :name="$icon" class="w-6 h-6" />
        </div>
        <div class="min-w-0">
            <span class="text-[11px] font-bold text-muted uppercase tracking-wider block truncate">{{ $label }}</span>
            <span class="text-2xl font-extrabold text-body leading-tight">{{ $value }}</span>
        </div>
    </div>
    @if($href)
        <div class="border-t border-line mt-4 pt-3">
            <a href="{{ $href }}" class="text-primary dark:text-accent hover:underline text-sm font-bold inline-flex items-center gap-1">
                <span>{{ $linkText ?? 'Ver' }}</span>
                <x-icon name="chevron-right" class="w-4 h-4" />
            </a>
        </div>
    @endif
</div>
