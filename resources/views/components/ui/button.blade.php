@props([
    'variant' => 'primary',   // primary | secondary | ghost | danger | success
    'size' => 'md',           // sm | md
    'href' => null,
    'icon' => null,
    'type' => 'button',
])
@php
    $base = 'inline-flex items-center justify-center gap-2 font-bold rounded-full transition-all focus-visible:outline-2 disabled:opacity-50 disabled:pointer-events-none';
    $sizes = [
        'sm' => 'text-xs px-4 py-2',
        'md' => 'text-sm px-5 py-2.5',
    ];
    $variants = [
        'primary'   => 'bg-primary text-on-primary hover:bg-primary-hover shadow-sm hover:-translate-y-0.5',
        'secondary' => 'bg-surface text-body border border-line hover:border-line-strong hover:bg-sunken',
        'ghost'     => 'text-muted hover:text-body hover:bg-sunken',
        'danger'    => 'bg-danger text-white hover:brightness-110 shadow-sm',
        'success'   => 'bg-success text-white hover:brightness-110 shadow-sm',
    ];
    $classes = trim($base.' '.($sizes[$size] ?? $sizes['md']).' '.($variants[$variant] ?? $variants['primary']));
@endphp
@if($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="w-4 h-4" />@endif
        {{ $slot }}
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if($icon)<x-icon :name="$icon" class="w-4 h-4" />@endif
        {{ $slot }}
    </button>
@endif
