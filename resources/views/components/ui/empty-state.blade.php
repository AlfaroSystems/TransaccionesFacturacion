@props([
    'icon' => 'document',
    'title' => 'Sin registros',
    'message' => null,
    'colspan' => null,   // si se usa dentro de una tabla
])
@if($colspan)
    <tr><td colspan="{{ $colspan }}" style="background:transparent;border:0;">
@endif
<div class="flex flex-col items-center justify-center text-center py-12">
    <div class="w-14 h-14 rounded-2xl bg-sunken text-subtle flex items-center justify-center mb-3">
        <x-icon :name="$icon" class="w-7 h-7" />
    </div>
    <p class="font-bold text-body">{{ $title }}</p>
    @if($message)<p class="text-sm text-muted mt-1">{{ $message }}</p>@endif
    @isset($slot){{ $slot }}@endisset
</div>
@if($colspan)
    </td></tr>
@endif
