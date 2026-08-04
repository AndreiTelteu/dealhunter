@props(['title', 'value', 'icon' => null, 'color' => 'blue', 'href' => null])

@php
// Spectral readout: mono value + placard label + one thin emission line.
// Legacy color names keep the component API stable across older screens.
$accents = [
    'blue'   => ['hex' => '#59e3ff', 'glow' => 'rgba(89,227,255,0.5)'],
    'cyan'   => ['hex' => '#59e3ff', 'glow' => 'rgba(89,227,255,0.5)'],
    'green'  => ['hex' => '#7dffa8', 'glow' => 'rgba(125,255,168,0.5)'],
    'purple' => ['hex' => '#59e3ff', 'glow' => 'rgba(89,227,255,0.5)'],
    'yellow' => ['hex' => '#ffc46b', 'glow' => 'rgba(255,196,107,0.5)'],
    'amber'  => ['hex' => '#ffc46b', 'glow' => 'rgba(255,196,107,0.5)'],
    'red'    => ['hex' => '#ff5d5d', 'glow' => 'rgba(255,93,93,0.5)'],
    'dim'    => ['hex' => '#8fa8b0', 'glow' => 'rgba(143,168,176,0.35)'],
];
$accent = $accents[$color] ?? $accents['blue'];
@endphp

<{{ $href ? 'a' : 'div' }} {{ $attributes->merge(['class' => 'group relative block bg-bench border border-hairline rounded-sm px-4 py-4 sm:px-5' . ($href ? ' hover:border-[rgba(89,227,255,0.35)] transition-colors focus-ring' : '')]) }}
    @if($href) href="{{ $href }}" @endif>
    <div class="flex items-start justify-between gap-3">
        <div class="min-w-0">
            <p class="placard text-[0.6rem] truncate">{{ $title }}</p>
            <p class="mt-2.5 font-mono text-2xl sm:text-[1.7rem] leading-none font-medium tabular-nums text-[#eaf4f6]">{{ $value }}</p>
        </div>
        @if($icon)
            <span class="shrink-0 text-dim/50 group-hover:text-beam transition-colors [&>svg]:h-4 [&>svg]:w-4" aria-hidden="true">{!! $icon !!}</span>
        @endif
    </div>
    <span class="mt-3.5 block h-px w-full" style="background: {{ $accent['hex'] }}; opacity: 0.85; box-shadow: 0 2px 4px rgba(0,0,0,0.7), 0 0 8px {{ $accent['glow'] }};" aria-hidden="true"></span>
</{{ $href ? 'a' : 'div' }}>
