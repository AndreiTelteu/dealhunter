@props([
    'height' => 0.5,      // 0..1 amplitude
    'state' => 'green',   // green | amber | red
    'delay' => 0,
])

@php
    $colors = [
        'green' => '#7dffa8',
        'amber' => '#ffc46b',
        'red'   => '#ff5d5d',
    ];
    $c = $colors[$state] ?? $colors['green'];
    $pct = max(0.04, min(1, $height)) * 100;
@endphp

<div class="relative h-full flex justify-center" aria-hidden="true">
    <div class="absolute bottom-0 w-[3px] spec-line line-ignite {{ $state === 'red' ? 'alert-live' : '' }}"
         style="height: {{ $pct }}%; background: {{ $c }}; color: {{ $c }}; animation-delay: {{ $delay }}s, {{ $delay }}s;">
    </div>
</div>
