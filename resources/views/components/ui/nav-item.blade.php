@props([
    'href' => '#',
    'icon' => 'dot',
    'active' => false,
    'label' => '',
])
<a href="{{ $href }}"
   @class(['nav-item', 'is-active' => $active])
   @if($label) title="{{ $label }}" @endif
   @if($active) aria-current="page" @endif>
    <x-icon :name="$icon" class="ui-ico w-5 h-5" />
    <span class="nav-label">{{ $label !== '' ? $label : $slot }}</span>
</a>
