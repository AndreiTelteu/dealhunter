@props([
    'label' => 'Instrument',
    'value' => '0',
    'unit' => '',
    'percent' => 0.5,   // 0..1 position of the needle across the sweep
    'state' => 'radium', // radium | amber | alert
    'note' => '',
])

@php
    // Needle sweep: from -120deg (parked, lower-left) to +120deg (lower-right), 240° total.
    $needleTo = -120 + (max(0, min(1, $percent)) * 240);

    $colors = [
        'radium' => ['signal' => '#8ff0a4', 'glow' => 'needle-glow', 'text' => 'text-radium'],
        'amber'  => ['signal' => '#ffb454', 'glow' => 'needle-glow', 'text' => 'text-amber-signal'],
        'alert'  => ['signal' => '#ff5347', 'glow' => 'needle-glow-alert', 'text' => 'text-alert'],
    ];
    $c = $colors[$state] ?? $colors['radium'];

    // Build tick marks around a 240° arc (from -120° to +120°), radius in a 100x100 viewBox.
    // Center at (50,54). Major ticks every 30°, minor every 6°.
    $cx = 50; $cy = 54; $rOuter = 40;
    $ticks = [];
    for ($deg = -120; $deg <= 120; $deg += 6) {
        $isMajor = ($deg % 30 === 0);
        $r1 = $isMajor ? $rOuter - 7 : $rOuter - 4;
        $rad = deg2rad($deg - 90); // -90 so 0deg points up
        $x1 = $cx + $r1 * cos($rad);      $y1 = $cy + $r1 * sin($rad);
        $x2 = $cx + $rOuter * cos($rad);  $y2 = $cy + $rOuter * sin($rad);
        $ticks[] = [$x1, $y1, $x2, $y2, $isMajor];
    }
@endphp

<figure {{ $attributes->merge(['class' => 'group']) }} role="img" aria-label="{{ $label }}: {{ $value }}{{ $unit ? ' '.$unit : '' }}. {{ $note }}">
    <div class="dial-bezel rounded-full p-[6px]">
        <div class="dial-face relative rounded-full aspect-square overflow-hidden">

            <svg viewBox="0 0 100 100" class="absolute inset-0 h-full w-full" aria-hidden="true">
                {{-- recessed scale ring --}}
                <circle cx="50" cy="54" r="40" fill="none" stroke="#232a2e" stroke-width="0.5" opacity="0.7"/>

                {{-- tick marks --}}
                @foreach ($ticks as [$x1, $y1, $x2, $y2, $isMajor])
                    <line x1="{{ $x1 }}" y1="{{ $y1 }}" x2="{{ $x2 }}" y2="{{ $y2 }}"
                          stroke="{{ $isMajor ? '#e8ecec' : '#5d6a6e' }}"
                          stroke-width="{{ $isMajor ? 0.9 : 0.5 }}"
                          opacity="{{ $isMajor ? 0.9 : 0.55 }}"/>
                @endforeach

                {{-- signal arc up to the needle position --}}
                @php
                    $arcRad = deg2rad($needleTo - 90);
                    $arcX = $cx + $rOuter * cos($arcRad);
                    $arcY = $cy + $rOuter * sin($arcRad);
                    $startRad = deg2rad(-120 - 90);
                    $sx = $cx + $rOuter * cos($startRad);
                    $sy = $cy + $rOuter * sin($startRad);
                    $largeArc = ($needleTo > 0) ? 1 : 0;
                @endphp
                <path d="M {{ $sx }} {{ $sy }} A {{ $rOuter }} {{ $rOuter }} 0 {{ $largeArc }} 1 {{ $arcX }} {{ $arcY }}"
                      fill="none" stroke="{{ $c['signal'] }}" stroke-width="1.4" stroke-linecap="round" opacity="0.85"/>

                {{-- needle (rotates from parked to reading) --}}
                <g class="needle-armed {{ $c['glow'] }}" style="--needle-from:-120deg; --needle-to:{{ $needleTo }}deg; transform-box: fill-box;">
                    <g transform="translate(50 54)">
                        <line x1="0" y1="8" x2="0" y2="-30" stroke="{{ $c['signal'] }}" stroke-width="1.6" stroke-linecap="round"/>
                        <circle cx="0" cy="0" r="3.4" fill="#0d1013" stroke="{{ $c['signal'] }}" stroke-width="1.2"/>
                    </g>
                </g>
            </svg>

            {{-- central readout window --}}
            <div class="absolute inset-x-0 bottom-[16%] flex flex-col items-center">
                <span class="font-mono font-bold tabular-nums leading-none text-2xl sm:text-[1.7rem] {{ $c['text'] }}" style="text-shadow:0 0 14px currentColor">
                    {{ $value }}
                </span>
                @if ($unit)
                    <span class="placard text-[0.55rem] mt-1">{{ $unit }}</span>
                @endif
            </div>
        </div>
    </div>

    <figcaption class="mt-4 text-center">
        <span class="placard block text-xs">{{ $label }}</span>
        @if ($note)
            <span class="mt-1 block text-xs text-[#5d6a6e] font-mono">{{ $note }}</span>
        @endif
    </figcaption>
</figure>
