@props([
    'href' => '#',
    'active' => false,
    'icon' => null,
])
<a href="{{ $href }}" @class(['subnav-link', 'is-active' => $active]) @if($active) aria-current="page" @endif>
    @if($icon)<x-icon :name="$icon" class="w-3.5 h-3.5" />@endif
    {{ $slot }}
</a>
