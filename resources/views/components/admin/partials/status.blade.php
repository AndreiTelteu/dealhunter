@props(['status', 'totalErrors' => 0])

@php
    $state = match ($status) {
        'healthy', 'completed' => $totalErrors > 0 ? 'warning' : 'healthy',
        'warning', 'partial' => 'warning',
        'critical', 'failed' => 'critical',
        default => 'pending',
    };
    $labels = [
        'healthy' => 'În regulă',
        'completed' => 'Finalizată',
        'warning' => 'Atenție',
        'partial' => 'Parțială',
        'critical' => 'Critică',
        'failed' => 'Eșuată',
        'started' => 'Pornită',
        'pending' => 'Necunoscută',
    ];
    $colors = [
        'healthy' => 'text-em-green',
        'warning' => 'text-em-amber',
        'critical' => 'text-em-red',
        'pending' => 'text-beam',
    ];
@endphp

<span {{ $attributes->merge(['class' => "inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase {$colors[$state]}"]) }}>
    <span class="spec-line h-1.5 w-1.5 {{ $state === 'healthy' ? 'bg-[#7dffa8]' : ($state === 'warning' ? 'bg-[#ffc46b]' : ($state === 'critical' ? 'bg-[#ff5d5d]' : 'bg-[#59e3ff]')) }} {{ $state === 'critical' ? 'alert-live' : '' }}" aria-hidden="true"></span>
    {{ $labels[$status] ?? ucfirst(str_replace('_', ' ', $status)) }}
</span>
