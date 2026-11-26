@props([
    'label' => null,
    'for' => null,
    'name' => null,        // para leer @error automáticamente
    'required' => false,
    'hint' => null,
])
@php $errName = $name ?? $for; @endphp
<div {{ $attributes->merge(['class' => 'min-w-0']) }}>
    @if($label)
        <label @if($for) for="{{ $for }}" @endif class="ui-label">
            {{ $label }}@if($required)<span class="text-danger"> *</span>@endif
        </label>
    @endif
    {{ $slot }}
    @if($hint)<p class="text-xs text-muted mt-1">{{ $hint }}</p>@endif
    @if($errName)
        @error($errName)<p class="text-danger text-[11px] font-semibold mt-1">{{ $message }}</p>@enderror
    @endif
</div>
