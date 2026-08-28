@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'font-medium text-xs text-emerald-600 dark:text-emerald-400']) }}>
        {{ __($status) }}
    </div>
@endif