@props([
    'align' => 'left',   // left | center | right
])
@php $a = ['left' => 'text-left', 'center' => 'text-center', 'right' => 'text-right'][$align] ?? 'text-left'; @endphp
<th {{ $attributes->merge(['class' => $a]) }}>{{ $slot }}</th>
