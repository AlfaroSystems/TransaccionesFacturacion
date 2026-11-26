@props([
    'icon' => 'eye',
    'tone' => 'info',    // info | warning | danger | success | primary | neutral
    'href' => null,
    'label' => '',        // texto accesible (title + aria-label)
    'as' => null,         // 'submit' para usar <button type=submit>
])
@php
    $tones = [
        'info'    => 'bg-info-soft text-info border-info-line',
        'warning' => 'bg-warning-soft text-warning border-warning-line',
        'danger'  => 'bg-danger-soft text-danger border-danger-line',
        'success' => 'bg-success-soft text-success border-success-line',
        'primary' => 'bg-primary-soft text-on-primary-soft border-transparent',
        'neutral' => 'bg-sunken text-muted border-line',
    ];
    $classes = 'inline-flex items-center justify-center p-2.5 rounded-xl border transition-all hover:brightness-95 '.($tones[$tone] ?? $tones['info']);
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }} @if($label) title="{{ $label }}" aria-label="{{ $label }}" @endif>
        <x-icon :name="$icon" class="w-4 h-4" />
    </a>
@else
    <button type="{{ $as === 'submit' ? 'submit' : 'button' }}" {{ $attributes->merge(['class' => $classes]) }} @if($label) title="{{ $label }}" aria-label="{{ $label }}" @endif>
        <x-icon :name="$icon" class="w-4 h-4" />
    </button>
@endif
