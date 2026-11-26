@props([
    'title' => '',
    'subtitle' => null,
    'eyebrow' => null,     // chip pequeño sobre el título
    'module' => null,      // texto gris junto al chip
    'back' => null,        // url del enlace "volver"
])
<header {{ $attributes->merge(['class' => 'flex flex-col md:flex-row md:items-center md:justify-between gap-4 mb-6']) }}>
    <div class="min-w-0">
        @if($eyebrow || $module || $back)
            <div class="flex items-center gap-2 mb-1.5">
                @if($back)
                    <a href="{{ $back }}" class="inline-flex items-center gap-1 text-xs font-bold text-muted hover:text-body">
                        <x-icon name="arrow-left" class="w-3.5 h-3.5" /> Volver
                    </a>
                    @if($eyebrow || $module)<span class="text-subtle">•</span>@endif
                @endif
                @if($eyebrow)
                    <span class="px-2.5 py-1 text-[11px] font-extrabold uppercase tracking-wider bg-primary-soft text-on-primary-soft rounded-lg">{{ $eyebrow }}</span>
                @endif
                @if($module)
                    @if($eyebrow)<span class="text-subtle">•</span>@endif
                    <span class="text-xs font-semibold text-muted">{{ $module }}</span>
                @endif
            </div>
        @endif
        <h1 class="text-2xl md:text-3xl font-extrabold text-primary dark:text-accent tracking-tight truncate">{{ $title }}</h1>
        @if($subtitle)
            <p class="text-muted text-sm mt-1">{{ $subtitle }}</p>
        @endif
    </div>
    @isset($actions)
        <div class="flex items-center gap-2.5 shrink-0">{{ $actions }}</div>
    @endisset
</header>
