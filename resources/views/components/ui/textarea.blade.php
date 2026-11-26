@props([
    'name' => null,
])
<textarea @if($name) name="{{ $name }}" id="{{ $attributes->get('id', $name) }}" @endif
    {{ $attributes->class(['ui-input', 'has-error' => $name && $errors->has($name)]) }}>{{ $slot }}</textarea>
