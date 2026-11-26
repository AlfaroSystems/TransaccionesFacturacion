@props([
    'padding' => 'p-6',
])
<div {{ $attributes->merge(['class' => 'ui-surface '.$padding]) }}>
    {{ $slot }}
</div>
