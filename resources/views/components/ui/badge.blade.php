@props([
    'tone' => 'neutral',   // success | warning | danger | info | primary | neutral
    'icon' => null,
])
@php
    $tones = [
        'success' => 'bg-success-soft text-success border-success-line',
        'warning' => 'bg-warning-soft text-warning border-warning-line',
        'danger'  => 'bg-danger-soft text-danger border-danger-line',
        'info'    => 'bg-info-soft text-info border-info-line',
        'primary' => 'bg-primary-soft text-on-primary-soft border-transparent',
        'neutral' => 'bg-sunken text-muted border-line',
    ];
    $classes = 'inline-flex items-center gap-1 px-2.5 py-0.5 text-[11px] font-extrabold rounded-full border uppercase tracking-wide '.($tones[$tone] ?? $tones['neutral']);
@endphp
<span {{ $attributes->merge(['class' => $classes]) }}>
    @if($icon)<x-icon :name="$icon" class="w-3 h-3" />@endif
    {{ $slot }}
</span>
