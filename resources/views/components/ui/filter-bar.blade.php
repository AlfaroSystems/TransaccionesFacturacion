@props([
    'action' => '',
    'method' => 'GET',
])
<x-ui.card padding="p-4" {{ $attributes }}>
    <form method="{{ $method }}" action="{{ $action }}" {{ $attributes->only('class') }}>
        {{ $slot }}
    </form>
</x-ui.card>
