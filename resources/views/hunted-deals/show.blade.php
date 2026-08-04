<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div class="min-w-0">
                <p class="placard text-[0.6rem] mb-1.5">Căutare urmărită</p>
                <h2 class="font-sans font-bold text-xl sm:text-2xl text-[#eaf4f6] leading-tight break-words">
                    {{ $huntedDeal->search_term }}
                </h2>
                <div class="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5">
                    @if($huntedDeal->is_active)
                        <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                            <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                            Activă
                        </span>
                    @else
                        <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-dim/70">
                            <span class="inline-block h-1 w-1 rounded-full bg-[#1c242a]" aria-hidden="true"></span>
                            În pauză
                        </span>
                    @endif
                    <span class="font-mono text-[0.7rem] tabular-nums text-dim/70">
                        @if($huntedDeal->last_crawled_at)
                            Ultima verificare {{ $huntedDeal->last_crawled_at->diffForHumans() }}
                        @else
                            Neverificată încă
                        @endif
                    </span>
                </div>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Toate căutările
                </a>
                <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Editează
                </a>
            </div>
        </div>
    </x-slot>
    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">
            <!-- ============ SPECTRAL READOUTS ============ -->
            <section aria-label="Statistici căutare">
                <div class="grid grid-cols-2 lg:grid-cols-5 gap-3 sm:gap-4">
                    <x-stat-card title="Anunțuri găsite" :value="$stats['total_deals']" color="cyan" />
                    <x-stat-card title="Noi în 24h" :value="$stats['new_deals_24h']" :color="$stats['new_deals_24h'] > 0 ? 'amber' : 'dim'" />
                    <x-stat-card title="Potrivite" :value="$stats['matching_intent']" :color="$stats['matching_intent'] > 0 ? 'green' : 'dim'" />
                    <x-stat-card title="Pare funcționale" :value="$stats['likely_working']" :color="$stats['likely_working'] > 0 ? 'green' : 'dim'" />
                    <x-stat-card title="Schimbări de preț" :value="$stats['price_drops']" :color="$stats['price_drops'] > 0 ? 'red' : 'dim'" />
                </div>
            </section>

            <!-- ============ NOTES ============ -->
            @if($huntedDeal->notes)
                <section aria-labelledby="notes-heading">
                    <div class="border border-hairline bg-bench px-5 py-4">
                        <h3 id="notes-heading" class="placard text-[0.65rem] mb-2.5">Notițe</h3>
                        <p class="text-sm text-dim whitespace-pre-wrap break-words" style="max-width:72ch">{{ $huntedDeal->notes }}</p>
                    </div>
                </section>
            @endif
            @php
                $hasTrace = $priceSnapshots->count() > 1;
                if ($hasTrace) {
                    $prices = $priceSnapshots->pluck('average_price')->map(fn($p) => (float) $p)->values();
                    $minP = $prices->min();
                    $maxP = $prices->max();
                    $rangeP = max($maxP - $minP, 1);
                    $firstT = $priceSnapshots->first()->captured_at->timestamp;
                    $lastT = $priceSnapshots->last()->captured_at->timestamp;
                    $rangeT = max($lastT - $firstT, 1);
                    $currency = $priceSnapshots->first()->price_currency ?? 'RON';
                    $sampleCounts = $priceSnapshots->pluck('deals_count');
                    // Build SVG trace points
                    $W = 760; $H = 190; $padL = 8; $padR = 8; $padT = 14; $padB = 30;
                    $innerW = $W - $padL - $padR; $innerH = $H - $padT - $padB;
                    $points = $priceSnapshots->map(function ($s) use ($padL, $padT, $innerW, $innerH, $minP, $rangeP, $firstT, $rangeT) {
                        $x = $padL + (($s->captured_at->timestamp - $firstT) / $rangeT) * $innerW;
                        $y = $padT + (1 - (((float) $s->average_price - $minP) / $rangeP)) * $innerH;
                        return [round($x, 1), round($y, 1), (float) $s->average_price];
                    });
                    $pathD = 'M ' . $points->map(fn($p) => $p[0] . ' ' . $p[1])->implode(' L ');
                    $firstPrice = $prices->first();
                    $lastPrice = $prices->last();
                    $delta = $lastPrice - $firstPrice;
                    $deltaPct = $firstPrice > 0 ? ($delta / $firstPrice) * 100 : 0;
                    $dropped = $delta < 0;
                    $traceColor = $dropped ? '#ff5d5d' : '#59e3ff';
                    $firstPt = $points->first();
                    $lastPt = $points->last();
                    // Y gridlines: min, mid, max
                    $yFor = function ($val) use ($padT, $innerH, $minP, $rangeP) {
                        return round($padT + (1 - (($val - $minP) / $rangeP)) * $innerH, 1);
                    };
                    $midP = $minP + $rangeP / 2;
                    $firstDate = $priceSnapshots->first()->captured_at;
                    $lastDate = $priceSnapshots->last()->captured_at;
                    $chartSamples = $priceSnapshots->map(fn ($s, $index) => [
                        'x' => $points[$index][0],
                        'y' => $points[$index][1],
                        'price' => number_format((float) $s->average_price, 0, ',', '.'),
                        'currency' => $currency,
                        'count' => $s->deals_count,
                        'captured' => $s->captured_at->format('d M Y, H:i'),
                    ])->values();
                }
            @endphp

            <!-- ============ FULL-PLATE SPECTRUM ============ -->
            <section aria-labelledby="spectrum-heading">
                <div class="border border-hairline graticule">
                    <div class="flex items-end justify-between flex-wrap gap-2 px-5 pt-4 pb-3 border-b border-hairline">
                        <div>
                            <h3 id="spectrum-heading" class="font-sans font-bold text-base sm:text-lg text-[#eaf4f6]">Spectrul căutării</h3>
                            <p class="mt-0.5 text-sm text-dim" style="max-width:60ch">
                                @if($hasTrace)
                                    Media de preț a anunțurilor potrivite și funcționale, instantaneu cu instantaneu.
                                @else
                                    Cum citește fasciculul această căutare.
                                @endif
                            </p>
                        </div>
                        @if($hasTrace)
                            <p class="font-mono text-[0.65rem] tabular-nums text-dim/70">
                                {{ $priceSnapshots->count() }} instantanee &middot; {{ $firstDate->format('d M H:i') }} &ndash; {{ $lastDate->format('d M H:i') }}
                            </p>
                        @endif
                    </div>

                    <div class="px-5 py-6">
                        <div class="grid grid-cols-1 lg:grid-cols-[1fr_15rem] gap-8 items-stretch">

                            <!-- ===== absorption trace: price history ===== -->
                            <div class="min-w-0">
                                @if($hasTrace)
                                    <p class="placard text-[0.6rem] mb-3">Medie de preț &middot; {{ $currency }}</p>
                                    <div class="relative">
                                        <div
                                            class="relative cursor-crosshair"
                                            x-data="{
                                                samples: {{ Js::from($chartSamples) }},
                                                chartWidth: {{ $W }},
                                                chartHeight: {{ $H }},
                                                padTop: {{ $padT }},
                                                padBottom: {{ $padB }},
                                                active: null,
                                                mouse: { x: 0, y: 0 },
                                                popWidth: 230,
                                                scan(event) {
                                                    const rect = event.currentTarget.getBoundingClientRect();
                                                    const px = (event.clientX - rect.left) * (this.chartWidth / rect.width);
                                                    let best = this.samples[0];
                                                    for (const sample of this.samples) {
                                                        if (Math.abs(sample.x - px) < Math.abs(best.x - px)) {
                                                            best = sample;
                                                        }
                                                    }
                                                    this.active = best;
                                                    this.mouse.x = event.clientX - rect.left;
                                                    this.mouse.y = event.clientY - rect.top;
                                                },
                                                park() {
                                                    this.active = null;
                                                },
                                                popLeft() {
                                                    const rect = this.$el.getBoundingClientRect();
                                                    if (this.mouse.x > rect.width * 0.62) {
                                                        return Math.max(0, this.mouse.x - this.popWidth - 18);
                                                    }
                                                    return Math.min(rect.width - this.popWidth, this.mouse.x + 18);
                                                },
                                                popTop() {
                                                    const rect = this.$el.getBoundingClientRect();
                                                    const y = this.mouse.y - 68;
                                                    return Math.min(Math.max(4, y), Math.max(4, rect.height - 96));
                                                }
                                            }"
                                            @mousemove="scan($event)"
                                            @mouseleave="park()"
                                        >
                                        <svg viewBox="0 0 {{ $W }} {{ $H }}" class="block w-full h-auto" role="img"
                                             aria-label="Evoluția mediei de preț: de la {{ number_format($firstPrice, 0, ',', '.') }} la {{ number_format($lastPrice, 0, ',', '.') }} {{ $currency }} ({{ $dropped ? 'scădere' : 'creștere' }} de {{ number_format(abs($deltaPct), 1, ',', '.') }}%).">
                                            <!-- horizontal graticule lines: min / mid / max -->
                                            <line x1="{{ $padL }}" y1="{{ $yFor($maxP) }}" x2="{{ $W - $padR }}" y2="{{ $yFor($maxP) }}" stroke="#1c242a" stroke-width="1"/>
                                            <line x1="{{ $padL }}" y1="{{ $yFor($midP) }}" x2="{{ $W - $padR }}" y2="{{ $yFor($midP) }}" stroke="#1c242a" stroke-width="1" stroke-dasharray="3 4"/>
                                            <line x1="{{ $padL }}" y1="{{ $yFor($minP) }}" x2="{{ $W - $padR }}" y2="{{ $yFor($minP) }}" stroke="#1c242a" stroke-width="1"/>

                                            <!-- the absorption trace -->
                                            <path d="{{ $pathD }}" fill="none" stroke="{{ $traceColor }}" stroke-width="1.6" stroke-linejoin="round" stroke-linecap="round"
                                                  style="filter: drop-shadow(0 2px 3px rgba(0,0,0,0.7)) drop-shadow(0 0 6px {{ $traceColor }});"/>

                                            <!-- start / end capture points -->
                                            <circle cx="{{ $firstPt[0] }}" cy="{{ $firstPt[1] }}" r="2.6" fill="#06080a" stroke="{{ $traceColor }}" stroke-width="1.4"/>
                                            <circle cx="{{ $lastPt[0] }}" cy="{{ $lastPt[1] }}" r="3" fill="{{ $traceColor }}" style="filter: drop-shadow(0 0 5px {{ $traceColor }});"/>

                                            <!-- live scan cursor -->
                                            <template x-if="active">
                                                <g aria-hidden="true">
                                                    <line :x1="active.x" :y1="padTop" :x2="active.x" :y2="chartHeight - padBottom" stroke="#8fa8b0" stroke-opacity="0.45" stroke-width="1" stroke-dasharray="2 3"/>
                                                    <circle :cx="active.x" :cy="active.y" r="3.6" fill="#06080a" stroke="{{ $traceColor }}" stroke-width="1.6" style="filter: drop-shadow(0 0 5px {{ $traceColor }});"/>
                                                </g>
                                            </template>
                                        </svg>

                                        <!-- engraved readout popover -->
                                        <div
                                            x-show="active"
                                            x-cloak
                                            x-transition.opacity.duration.150ms
                                            class="pointer-events-none absolute z-10 border border-hairline bg-[#06080a] px-3.5 py-2.5"
                                            :style="`left: ${popLeft()}px; top: ${popTop()}px; width: ${popWidth}px; box-shadow: 0 10px 24px rgba(0,0,0,.6), inset 0 0 0 1px rgba(89,227,255,.08);`"
                                        >
                                            <template x-if="active">
                                                <div>
                                                    <p class="placard text-[0.55rem]">Medie instantanee</p>
                                                    <p class="mt-1.5 font-mono text-base font-bold tabular-nums" style="color: {{ $traceColor }}; text-shadow: 0 2px 6px rgba(0,0,0,.7), 0 0 12px {{ $traceColor }}40;">
                                                        <span x-text="active.price"></span> <span class="text-[0.65rem] font-normal text-dim" x-text="active.currency"></span>
                                                    </p>
                                                    <p class="mt-1 font-mono text-[0.62rem] tabular-nums text-dim/80"><span x-text="active.captured"></span> &middot; <span x-text="active.count"></span> anunțuri</p>
                                                </div>
                                            </template>
                                        </div>
                                        </div>

                                        <!-- axis scale: min / mid / max price -->
                                        <div class="mt-1.5 flex justify-between font-mono text-[0.6rem] tabular-nums text-dim/60">
                                            <span>{{ number_format($minP, 0, ',', '.') }}</span>
                                            <span>{{ number_format($midP, 0, ',', '.') }}</span>
                                            <span>{{ number_format($maxP, 0, ',', '.') }}</span>
                                        </div>
                                    </div>

                                    <!-- trace verdict readouts -->
                                    <dl class="mt-5 grid grid-cols-2 sm:grid-cols-4 gap-x-4 gap-y-4 border-t border-hairline pt-4">
                                        <div>
                                            <dt class="placard text-[0.58rem]">Prima medie</dt>
                                            <dd class="mt-1.5 font-mono text-base tabular-nums text-[#eaf4f6]">{{ number_format($firstPrice, 0, ',', '.') }} <span class="text-[0.65rem] text-dim/70">{{ $currency }}</span></dd>
                                        </div>
                                        <div>
                                            <dt class="placard text-[0.58rem]">Ultima medie</dt>
                                            <dd class="mt-1.5 font-mono text-base tabular-nums text-[#eaf4f6]">{{ number_format($lastPrice, 0, ',', '.') }} <span class="text-[0.65rem] text-dim/70">{{ $currency }}</span></dd>
                                        </div>
                                        <div>
                                            <dt class="placard text-[0.58rem]">Variație</dt>
                                            <dd class="mt-1.5 font-mono text-base tabular-nums {{ $dropped ? 'text-em-red' : 'text-em-green' }}">
                                                {{ $delta > 0 ? '+' : '' }}{{ number_format($delta, 0, ',', '.') }}
                                            </dd>
                                        </div>
                                        <div>
                                            <dt class="placard text-[0.58rem]">Min / Max medie</dt>
                                            <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-dim leading-snug">{{ number_format($minP, 0, ',', '.') }}<br>{{ number_format($maxP, 0, ',', '.') }}</dd>
                                        </div>
                                    </dl>
                                    <p class="mt-3 font-mono text-[0.62rem] tabular-nums text-dim/60">
                                        Ultimul instantaneu: media a {{ $priceSnapshots->last()->deals_count }} anunțuri potrivite și funcționale cu preț &middot; {{ $priceSnapshots->last()->captured_at->format('d M, H:i') }}
                                    </p>
                                @else
                                    <!-- parked trace: no average price snapshots yet -->
                                    <p class="placard text-[0.6rem] mb-3">Medie de preț</p>
                                    <div class="relative h-28 flex items-center" aria-hidden="true">
                                        <div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                                        <div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div>
                                    </div>
                                    <p class="text-sm text-dim mt-3" style="max-width:52ch">
                                        Încă nu există suficiente instantanee orare pentru a trasa media de preț a anunțurilor potrivite și funcționale. Trasarea apare după cel puțin două instantanee.
                                    </p>
                                @endif
                            </div>

                            <!-- ===== emission verdict lines ===== -->
                            <div class="min-w-0 lg:border-l lg:border-hairline lg:pl-6">
                                <p class="placard text-[0.6rem] mb-3">Lecturi</p>
                                <div class="flex items-end gap-5 h-28" role="img"
                                     aria-label="Lecturi spectrale: {{ $stats['total_deals'] }} anunțuri găsite, {{ $stats['matching_intent'] }} potrivite, {{ $stats['likely_working'] }} pare funcționale, {{ $stats['price_drops'] }} schimbări de preț.">
                                    @php
                                        $maxCount = max($stats['total_deals'], 1);
                                        $line = function ($count, $color) use ($maxCount) {
                                            $h = max(6, ($count / $maxCount) * 100);
                                            return [$h, $color];
                                        };
                                        [$hTotal, $cTotal] = $line($stats['total_deals'], '#59e3ff');
                                        [$hMatch, $cMatch] = $line($stats['matching_intent'], '#7dffa8');
                                        [$hWork, $cWork] = $line($stats['likely_working'], '#7dffa8');
                                        [$hDrop, $cDrop] = $line($stats['price_drops'], '#ff5d5d');
                                    @endphp
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-[3px] spec-line" style="height:{{ $hTotal }}px; background:{{ $cTotal }}; color:{{ $cTotal }};" aria-hidden="true"></div>
                                        <span class="font-mono text-[0.55rem] uppercase text-dim/70">Găsite</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-[3px] spec-line" style="height:{{ $hMatch }}px; background:{{ $cMatch }}; color:{{ $cMatch }};" aria-hidden="true"></div>
                                        <span class="font-mono text-[0.55rem] uppercase text-dim/70">Potr.</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-[3px] spec-line" style="height:{{ $hWork }}px; background:{{ $cWork }}; color:{{ $cWork }};" aria-hidden="true"></div>
                                        <span class="font-mono text-[0.55rem] uppercase text-dim/70">Func.</span>
                                    </div>
                                    <div class="flex flex-col items-center gap-2 flex-1">
                                        <div class="w-[3px] spec-line {{ $stats['price_drops'] > 0 ? 'alert-live' : '' }}" style="height:{{ $hDrop }}px; background:{{ $cDrop }}; color:{{ $cDrop }};" aria-hidden="true"></div>
                                        <span class="font-mono text-[0.55rem] uppercase text-dim/70">Preț</span>
                                    </div>
                                </div>
                                <dl class="mt-4 space-y-2 border-t border-hairline pt-3">
                                    <div class="flex justify-between font-mono text-[0.7rem] tabular-nums"><dt class="text-dim">Găsite</dt><dd class="text-beam">{{ $stats['total_deals'] }}</dd></div>
                                    <div class="flex justify-between font-mono text-[0.7rem] tabular-nums"><dt class="text-dim">Potrivite</dt><dd class="text-em-green">{{ $stats['matching_intent'] }}</dd></div>
                                    <div class="flex justify-between font-mono text-[0.7rem] tabular-nums"><dt class="text-dim">Funcționale</dt><dd class="text-em-green">{{ $stats['likely_working'] }}</dd></div>
                                    <div class="flex justify-between font-mono text-[0.7rem] tabular-nums"><dt class="text-dim">Schimb. preț</dt><dd class="{{ $stats['price_drops'] > 0 ? 'text-em-red' : 'text-dim/70' }}">{{ $stats['price_drops'] }}</dd></div>
                                </dl>
                            </div>

                        </div>
                    </div>
                </div>
            </section>
            <!-- ============ ASSOCIATED DEALS LEDGER ============ -->
            <section aria-labelledby="deals-heading">
                <div class="flex items-end justify-between flex-wrap gap-3 mb-4">
                    <div>
                        <h3 id="deals-heading" class="font-sans font-bold text-lg sm:text-xl text-[#eaf4f6]">Anunțuri găsite</h3>
                        <p class="mt-1 text-sm text-dim" style="max-width:56ch">Anunțurile de pe OLX România care se potrivesc acestei căutări.</p>
                    </div>
                    @if($deals->total() > 0)
                        <a href="{{ route('deals.index', ['hunted_deal' => $huntedDeal->id]) }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">
                            Toate în Anunțuri &rarr;
                        </a>
                    @endif
                </div>

                <!-- ============ FILTERS ============ -->
                <form method="GET" action="{{ route('hunted-deals.show', $huntedDeal) }}" class="border border-hairline bg-bench px-5 py-5 mb-6" aria-label="Filtre anunțuri">
                    <div class="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_12rem_9rem]">
                        <div>
                            <label for="search" class="placard text-[0.6rem]">Caută în anunțuri</label>
                            <input id="search" name="search" type="search" value="{{ request('search') }}" placeholder="Titlu sau descriere..."
                                class="mt-2 block w-full rounded-none border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                        </div>
                        <div>
                            <label for="sort" class="placard text-[0.6rem]">Ordine registru</label>
                            <select id="sort" name="sort" class="mt-2 block w-full rounded-none border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                                <option value="last_seen_at" @selected(request('sort', 'last_seen_at') === 'last_seen_at')>Ultima apariție</option>
                                <option value="created_at" @selected(request('sort') === 'created_at')>Adăugat</option>
                                <option value="title" @selected(request('sort') === 'title')>Titlu</option>
                                <option value="price_amount" @selected(request('sort') === 'price_amount')>Preț</option>
                                <option value="location" @selected(request('sort') === 'location')>Locație</option>
                            </select>
                        </div>
                        <div>
                            <label for="direction" class="placard text-[0.6rem]">Sens</label>
                            <select id="direction" name="direction" class="mt-2 block w-full rounded-none border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Descrescător</option>
                                <option value="asc" @selected(request('direction') === 'asc')>Crescător</option>
                            </select>
                        </div>
                    </div>

                    <div class="mt-5 border-t border-hairline pt-4">
                        <p class="placard mb-3 text-[0.6rem]">Restrânge citirea</p>
                        <div class="grid grid-cols-1 gap-x-5 gap-y-2.5 sm:grid-cols-2 xl:grid-cols-4">
                            <label class="flex cursor-pointer items-center justify-between gap-3 border-b border-hairline pb-2.5 font-mono text-[0.7rem] tabular-nums text-dim">
                                <span class="flex items-center gap-2"><input type="checkbox" name="price_drops" value="1" @checked(request()->boolean('price_drops')) class="rounded-none border-hairline bg-[#06080a] text-[#ff5d5d] focus:ring-[#ff5d5d]/40"> Preț redus</span>
                                <span class="text-em-red">{{ $filterCounts['price_drops'] }}</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 border-b border-hairline pb-2.5 font-mono text-[0.7rem] tabular-nums text-dim">
                                <span class="flex items-center gap-2"><input type="checkbox" name="new_items" value="1" @checked(request()->boolean('new_items')) class="rounded-none border-hairline bg-[#06080a] text-[#ffc46b] focus:ring-[#ffc46b]/40"> Noi, 24 h</span>
                                <span class="text-em-amber">{{ $filterCounts['new_items'] }}</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 border-b border-hairline pb-2.5 font-mono text-[0.7rem] tabular-nums text-dim">
                                <span class="flex items-center gap-2"><input type="hidden" name="matches_intent" value="0"><input type="checkbox" name="matches_intent" value="1" @checked($matchesIntentFilter) class="rounded-none border-hairline bg-[#06080a] text-[#7dffa8] focus:ring-[#7dffa8]/40"> Doar potriviri</span>
                                <span class="text-em-green">{{ $filterCounts['matches_intent'] }}</span>
                            </label>
                            <label class="flex cursor-pointer items-center justify-between gap-3 border-b border-hairline pb-2.5 font-mono text-[0.7rem] tabular-nums text-dim">
                                <span class="flex items-center gap-2"><input type="checkbox" name="likely_working" value="1" @checked(request()->boolean('likely_working')) class="rounded-none border-hairline bg-[#06080a] text-[#7dffa8] focus:ring-[#7dffa8]/40"> Funcționale</span>
                                <span class="text-em-green">{{ $filterCounts['likely_working'] }}</span>
                            </label>
                        </div>
                    </div>

                    <div class="mt-5 flex flex-wrap items-center justify-end gap-2.5">
                        <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Resetează</a>
                        <button type="submit" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Aplică</button>
                    </div>
                </form>

                @if($deals->count() > 0)
                    <div class="border-t border-hairline">
                        @foreach($deals as $deal)
                            <div class="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <x-favorite-button :deal="$deal" />
                                            <a href="{{ route('deals.show', $deal) }}" class="focus-ring rounded-sm font-sans font-semibold text-[#eaf4f6] group-hover:text-beam transition-colors break-words">
                                                {{ Str::limit($deal->title, 80) }}
                                            </a>
                                            @if($deal->matches_intent)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                                    <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                                                    Potrivit @if($deal->intent_score !== null) {{ $deal->intent_score }}% @endif
                                                </span>
                                            @endif
                                            @if($deal->likely_working)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                                    <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                                                    Funcțional
                                                </span>
                                            @endif
                                            @if($deal->created_at >= now()->subDay())
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-amber">
                                                    <span class="inline-block h-1 w-1 rounded-full bg-[#ffc46b]" style="box-shadow: 0 0 6px rgba(255,196,107,0.6);" aria-hidden="true"></span>
                                                    Nou
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            @if($deal->price_amount)
                                                <span class="text-[#eaf4f6]">{{ number_format($deal->price_amount, 0, ',', '.') }} {{ $deal->price_currency }}</span> &middot;
                                            @else
                                                <span>Fără preț</span> &middot;
                                            @endif
                                            @if($deal->location)
                                                {{ $deal->location }} &middot;
                                            @endif
                                            {{ $deal->created_at->diffForHumans() }}
                                        </p>
                                        @if($deal->description)
                                            <p class="mt-1 text-sm text-dim break-words" style="max-width:68ch">{{ Str::limit($deal->description, 120) }}</p>
                                        @endif
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4 sm:gap-5">
                                        <a href="{{ route('deals.show', $deal) }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">Detalii</a>
                                        <a href="{{ $deal->url }}" target="_blank" rel="noopener" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">OLX &#8599;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>

                    @if($deals->hasPages())
                        <div class="mt-6">
                            {{ $deals->links() }}
                        </div>
                    @endif
                @else
                    <!-- ============ EMPTY STATE: PARKED BEAM ============ -->
                    <div class="relative border border-hairline graticule">
                        <div class="px-6 py-14 sm:py-16 text-center">
                            <div class="relative mx-auto mb-8 h-16 max-w-md" aria-hidden="true">
                                <div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                                <div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div>
                            </div>
                            @if(request()->hasAny(['search', 'price_drops', 'new_items', 'matches_intent', 'likely_working']))
                                <h4 class="font-sans font-bold text-lg text-[#eaf4f6]">Niciun anunț nu corespunde</h4>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">Schimbă termenul sau filtrele și încearcă din nou.</p>
                                <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="beamkey focus-ring mt-7 rounded-sm px-6 py-3 text-[0.7rem]">Resetează filtrele</a>
                            @else
                                <h4 class="font-sans font-bold text-lg text-[#eaf4f6]">Niciun anunț găsit încă</h4>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">
                                    @if($huntedDeal->is_active)
                                        Căutarea este activă. Verificarea automată caută „{{ $huntedDeal->search_term }}" pe OLX România la următoarea rulare, iar anunțurile apar aici.
                                    @else
                                        Această căutare este în pauză, așa că nu adună anunțuri. Activeaz-o pentru a începe urmărirea.
                                    @endif
                                </p>
                                @if(!$huntedDeal->is_active)
                                    <div class="mt-7">
                                        <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                                            Activează căutarea
                                        </a>
                                    </div>
                                @endif
                            @endif
                        </div>
                    </div>
                @endif
            </section>

            <!-- ============ METADATA ============ -->
            <section aria-labelledby="meta-heading">
                <div class="border border-hairline bg-bench px-5 py-5">
                    <h3 id="meta-heading" class="placard text-[0.65rem] mb-4">Informații căutare</h3>
                    <dl class="grid grid-cols-2 lg:grid-cols-4 gap-x-4 gap-y-5">
                        <div>
                            <dt class="placard text-[0.58rem]">Creată</dt>
                            <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->created_at->format('d M Y') }}</dd>
                            <dd class="font-mono text-[0.68rem] tabular-nums text-dim/70">{{ $huntedDeal->created_at->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="placard text-[0.58rem]">Actualizată</dt>
                            <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->updated_at->format('d M Y') }}</dd>
                            <dd class="font-mono text-[0.68rem] tabular-nums text-dim/70">{{ $huntedDeal->updated_at->format('H:i') }}</dd>
                        </div>
                        <div>
                            <dt class="placard text-[0.58rem]">Ultima verificare</dt>
                            @if($huntedDeal->last_crawled_at)
                                <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{{ $huntedDeal->last_crawled_at->format('d M Y') }}</dd>
                                <dd class="font-mono text-[0.68rem] tabular-nums text-dim/70">{{ $huntedDeal->last_crawled_at->format('H:i') }}</dd>
                            @else
                                <dd class="mt-1.5 font-mono text-[0.8rem] tabular-nums text-em-amber">Neverificată</dd>
                            @endif
                        </div>
                        <div>
                            <dt class="placard text-[0.58rem]">Stare</dt>
                            <dd class="mt-1.5">
                                @if($huntedDeal->is_active)
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[0.7rem] uppercase text-em-green">
                                        <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                                        Activă
                                    </span>
                                @else
                                    <span class="inline-flex items-center gap-1.5 font-mono text-[0.7rem] uppercase text-dim/70">
                                        <span class="inline-block h-1 w-1 rounded-full bg-[#1c242a]" aria-hidden="true"></span>
                                        În pauză
                                    </span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </section>
        </div>
    </div>
</x-app-layout>
