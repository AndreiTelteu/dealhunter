<x-app-layout>
    @php
        $currentSnapshot = $deal->latestSnapshot;
        $currentDeal = $currentSnapshot ?? $deal;
        $priceSnapshots = $deal->snapshots->filter(fn ($snapshot) => $snapshot->price_amount !== null)->sortBy('captured_at')->values();
        $hasTrace = $priceSnapshots->count() > 1;

        if ($hasTrace) {
            $prices = $priceSnapshots->pluck('price_amount')->map(fn ($price) => (float) $price)->values();
            $minPrice = $prices->min();
            $maxPrice = $prices->max();
            $priceRange = max($maxPrice - $minPrice, 1);
            $firstTimestamp = $priceSnapshots->first()->captured_at->timestamp;
            $lastTimestamp = $priceSnapshots->last()->captured_at->timestamp;
            $timeRange = max($lastTimestamp - $firstTimestamp, 1);
            $chartWidth = 760;
            $chartHeight = 190;
            $padLeft = 8;
            $padRight = 8;
            $padTop = 14;
            $padBottom = 30;
            $innerWidth = $chartWidth - $padLeft - $padRight;
            $innerHeight = $chartHeight - $padTop - $padBottom;
            $points = $priceSnapshots->map(function ($snapshot) use ($padLeft, $padTop, $innerWidth, $innerHeight, $minPrice, $priceRange, $firstTimestamp, $timeRange) {
                $x = $padLeft + (($snapshot->captured_at->timestamp - $firstTimestamp) / $timeRange) * $innerWidth;
                $y = $padTop + (1 - (((float) $snapshot->price_amount - $minPrice) / $priceRange)) * $innerHeight;

                return [round($x, 1), round($y, 1), (float) $snapshot->price_amount];
            });
            $chartSamples = $priceSnapshots->map(fn ($snapshot, $index) => [
                'x' => $points[$index][0],
                'y' => $points[$index][1],
                'price' => number_format((float) $snapshot->price_amount, 0, ',', '.'),
                'currency' => $snapshot->price_currency ?? 'RON',
                'captured' => $snapshot->captured_at->format('d M Y, H:i'),
            ])->values();
            $path = 'M '.$points->map(fn ($point) => $point[0].' '.$point[1])->implode(' L ');
            $firstPrice = $prices->first();
            $lastPrice = $prices->last();
            $change = $lastPrice - $firstPrice;
            $changePercent = $firstPrice > 0 ? ($change / $firstPrice) * 100 : 0;
            $hasPriceDrop = $change < 0;
            $traceColor = $hasPriceDrop ? '#ff5d5d' : '#59e3ff';
            $midPrice = $minPrice + $priceRange / 2;
            $yFor = fn ($value) => round($padTop + (1 - (($value - $minPrice) / $priceRange)) * $innerHeight, 1);
        }
        $imageUrls = $currentDeal->image_urls ?? $deal->image_urls ?? [];
    @endphp

    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div class="min-w-0">
                <p class="placard mb-1.5 text-[0.6rem]">Fișa anunțului</p>
                <h2 class="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">{{ $currentDeal->title }}</h2>
                <p class="mt-2 text-sm text-dim">Căutare: <a href="{{ route('hunted-deals.show', $deal->huntedDeal) }}" class="text-beam hover:underline">{{ $deal->huntedDeal->search_term }}</a></p>
            </div>
            <div class="flex shrink-0 flex-wrap items-center gap-2.5">
                <x-favorite-button :deal="$deal" />
                <a href="{{ route('deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">&larr; Anunțuri</a>
                @if($deal->url)<a href="{{ $deal->url }}" target="_blank" rel="noopener" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Deschide OLX &#8599;</a>@endif
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section class="border border-hairline graticule" aria-labelledby="readout-heading">
                <div class="grid lg:grid-cols-[minmax(0,1fr)_18rem]">
                    <div class="border-b border-hairline px-5 py-6 lg:border-b-0 lg:border-r">
                        <p class="placard text-[0.6rem]">Citire curentă</p>
                        <div class="mt-4 flex flex-wrap items-end justify-between gap-5">
                            <div>
                                <h3 id="readout-heading" class="font-sans text-lg font-bold text-[#eaf4f6]">Preț curent</h3>
                                <p class="mt-1 font-mono text-3xl font-bold tabular-nums text-beam" style="text-shadow:0 3px 8px rgba(0,0,0,.7), 0 0 16px rgba(89,227,255,.35)">
                                    @if($currentDeal->price_amount){{ number_format($currentDeal->price_amount, 0, ',', '.') }} {{ $currentDeal->price_currency }}@else<span class="text-lg font-normal text-dim">Fără preț</span>@endif
                                </p>
                                @if($currentDeal->price_raw && $currentDeal->price_raw !== $currentDeal->price_amount.' '.$currentDeal->price_currency)<p class="mt-1 font-mono text-[0.65rem] text-dim/70">text OLX: {{ $currentDeal->price_raw }}</p>@endif
                            </div>
                            <dl class="grid grid-cols-2 gap-x-8 gap-y-3 font-mono text-[0.7rem] tabular-nums">
                                <div><dt class="placard text-[0.55rem]">Instantaneu</dt><dd class="mt-1 text-[#eaf4f6]">{{ $currentSnapshot?->captured_at?->format('d M Y, H:i') ?? 'fără captură' }}</dd></div>
                                <div><dt class="placard text-[0.55rem]">Ultima apariție</dt><dd class="mt-1 text-[#eaf4f6]">{{ $deal->last_seen_at?->format('d M Y, H:i') ?? 'fără dată' }}</dd></div>
                            </dl>
                        </div>
                    </div>
                    <div class="bg-bench px-5 py-6">
                        <p class="placard text-[0.6rem]">Clasificare</p>
                        <dl class="mt-4 space-y-3 font-mono text-[0.75rem] tabular-nums">
                            @if($currentDeal->matches_intent !== null)<div class="flex items-center justify-between gap-4 border-b border-hairline pb-3"><dt class="text-dim">Potrivire</dt><dd class="{{ $currentDeal->matches_intent ? 'text-em-green' : 'text-em-amber' }}">{{ $currentDeal->matches_intent ? 'Da' : 'Nu' }} @if($currentDeal->intent_score !== null) ({{ $currentDeal->intent_score }}%) @endif</dd></div>@endif
                            @if($currentDeal->likely_working !== null)<div class="flex items-center justify-between gap-4 border-b border-hairline pb-3"><dt class="text-dim">Pare funcțional</dt><dd class="{{ $currentDeal->likely_working ? 'text-em-green' : 'text-em-amber' }}">{{ $currentDeal->likely_working ? 'Da' : 'Nu' }}</dd></div>@endif
                            @if($currentDeal->confidence !== null)<div class="flex items-center justify-between gap-4"><dt class="text-dim">Încredere</dt><dd class="text-[#eaf4f6]">{{ round($currentDeal->confidence * 100) }}%</dd></div>@endif
                            @if($currentDeal->matches_intent === null && $currentDeal->likely_working === null)<div class="text-em-amber">Fără clasificare</div>@endif
                        </dl>
                    </div>
                </div>
            </section>

            <section class="border border-hairline graticule" aria-labelledby="trace-heading">
                <div class="flex flex-wrap items-end justify-between gap-3 border-b border-hairline px-5 py-4">
                    <div><p class="placard text-[0.6rem]">Istoric real</p><h3 id="trace-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Evoluția prețului</h3></div>
                    <p class="font-mono text-[0.65rem] tabular-nums text-dim/70">{{ $priceSnapshots->count() }} {{ $priceSnapshots->count() === 1 ? 'preț înregistrat' : 'prețuri înregistrate' }}</p>
                </div>
                <div class="px-5 py-6">
                    @if($hasTrace)
                        <div
                            class="relative cursor-crosshair"
                            x-data="{
                                samples: {{ Js::from($chartSamples) }},
                                chartWidth: {{ $chartWidth }},
                                chartHeight: {{ $chartHeight }},
                                padTop: {{ $padTop }},
                                padBottom: {{ $padBottom }},
                                active: null,
                                mouse: { x: 0, y: 0 },
                                popWidth: 220,
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
                        <svg viewBox="0 0 {{ $chartWidth }} {{ $chartHeight }}" class="block w-full" role="img" aria-label="Preț de la {{ number_format($firstPrice, 0, ',', '.') }} la {{ number_format($lastPrice, 0, ',', '.') }} {{ $priceSnapshots->last()->price_currency }}.">
                            <line x1="{{ $padLeft }}" y1="{{ $yFor($maxPrice) }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $yFor($maxPrice) }}" stroke="#1c242a" stroke-width="1" />
                            <line x1="{{ $padLeft }}" y1="{{ $yFor($midPrice) }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $yFor($midPrice) }}" stroke="#1c242a" stroke-width="1" stroke-dasharray="3 4" />
                            <line x1="{{ $padLeft }}" y1="{{ $yFor($minPrice) }}" x2="{{ $chartWidth - $padRight }}" y2="{{ $yFor($minPrice) }}" stroke="#1c242a" stroke-width="1" />
                            <path d="{{ $path }}" fill="none" stroke="{{ $traceColor }}" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round" style="filter:drop-shadow(0 3px 3px rgba(0,0,0,.7)) drop-shadow(0 0 6px {{ $traceColor }});" />
                            <circle cx="{{ $points->first()[0] }}" cy="{{ $points->first()[1] }}" r="2.6" fill="#06080a" stroke="{{ $traceColor }}" stroke-width="1.4" />
                            <circle class="{{ $hasPriceDrop ? 'alert-live' : '' }}" cx="{{ $points->last()[0] }}" cy="{{ $points->last()[1] }}" r="3" fill="{{ $traceColor }}" style="filter:drop-shadow(0 0 5px {{ $traceColor }});" />
                            <template x-if="active">
                                <g aria-hidden="true">
                                    <line :x1="active.x" :y1="padTop" :x2="active.x" :y2="chartHeight - padBottom" stroke="#8fa8b0" stroke-opacity="0.45" stroke-width="1" stroke-dasharray="2 3" />
                                    <circle :cx="active.x" :cy="active.y" r="3.6" fill="#06080a" stroke="{{ $traceColor }}" stroke-width="1.6" style="filter:drop-shadow(0 0 5px {{ $traceColor }});" />
                                </g>
                            </template>
                        </svg>
                        <div
                            x-show="active"
                            x-cloak
                            x-transition.opacity.duration.150ms
                            class="pointer-events-none absolute z-10 border border-hairline bg-[#06080a] px-3.5 py-2.5"
                            :style="`left: ${popLeft()}px; top: ${popTop()}px; width: ${popWidth}px; box-shadow: 0 10px 24px rgba(0,0,0,.6), inset 0 0 0 1px rgba(89,227,255,.08);`"
                        >
                            <template x-if="active">
                                <div>
                                    <p class="placard text-[0.55rem]">Citire instantanee</p>
                                    <p class="mt-1.5 font-mono text-base font-bold tabular-nums" style="color: {{ $traceColor }}; text-shadow: 0 2px 6px rgba(0,0,0,.7), 0 0 12px {{ $traceColor }}40;">
                                        <span x-text="active.price"></span> <span class="text-[0.65rem] font-normal text-dim" x-text="active.currency"></span>
                                    </p>
                                    <p class="mt-1 font-mono text-[0.62rem] tabular-nums text-dim/80" x-text="active.captured"></p>
                                </div>
                            </template>
                        </div>
                        </div>
                        <div class="mt-1.5 flex justify-between font-mono text-[0.6rem] tabular-nums text-dim/60"><span>min {{ number_format($minPrice, 0, ',', '.') }}</span><span>mijloc {{ number_format($midPrice, 0, ',', '.') }}</span><span>max {{ number_format($maxPrice, 0, ',', '.') }}</span></div>
                        <dl class="mt-5 grid grid-cols-2 gap-x-5 gap-y-4 border-t border-hairline pt-4 sm:grid-cols-4">
                            <div><dt class="placard text-[0.55rem]">Primul</dt><dd class="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">{{ number_format($firstPrice, 0, ',', '.') }} {{ $priceSnapshots->first()->price_currency }}</dd></div>
                            <div><dt class="placard text-[0.55rem]">Ultimul</dt><dd class="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">{{ number_format($lastPrice, 0, ',', '.') }} {{ $priceSnapshots->last()->price_currency }}</dd></div>
                            <div><dt class="placard text-[0.55rem]">Variație</dt><dd class="mt-1 font-mono text-sm tabular-nums {{ $hasPriceDrop ? 'text-em-red' : 'text-beam' }}">{{ $change > 0 ? '+' : '' }}{{ number_format($change, 0, ',', '.') }} ({{ $change > 0 ? '+' : '' }}{{ number_format($changePercent, 1, ',', '.') }}%)</dd></div>
                            <div><dt class="placard text-[0.55rem]">Interval</dt><dd class="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">{{ $priceSnapshots->first()->captured_at->format('d M') }} — {{ $priceSnapshots->last()->captured_at->format('d M') }}</dd></div>
                        </dl>
                    @else
                        <div class="relative h-28" aria-hidden="true"><div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div><div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div></div>
                        <p class="mt-3 text-sm text-dim">Sunt necesare cel puțin două prețuri pentru evoluție.</p>
                    @endif
                </div>
            </section>

            <div class="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                <div class="space-y-8">
                    @if($currentDeal->description)
                        <section class="border border-hairline bg-bench px-5 py-5" aria-labelledby="description-heading"><p class="placard text-[0.6rem]">Descriere OLX</p><h3 id="description-heading" class="sr-only">Descriere</h3><p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-dim" style="max-width:72ch">{{ $currentDeal->description }}</p></section>
                    @endif

                    @if($deal->snapshots->count() > 0)
                        <section aria-labelledby="history-heading">
                            <div class="mb-4"><p class="placard text-[0.6rem]">Jurnal</p><h3 id="history-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Instantanee păstrate</h3></div>
                            <div class="border-t border-hairline">
                                @foreach($deal->snapshots as $snapshot)
                                    <article class="grid gap-3 border-b border-hairline py-4 sm:grid-cols-[10rem_minmax(0,1fr)]">
                                        <p class="font-mono text-[0.7rem] tabular-nums text-dim/70">{{ $snapshot->captured_at->format('d M Y, H:i') }}</p>
                                        <div class="min-w-0"><div class="flex flex-wrap gap-x-4 gap-y-1 font-mono text-[0.7rem] tabular-nums"><span class="text-[#eaf4f6]">{{ $snapshot->price_amount ? number_format($snapshot->price_amount, 0, ',', '.').' '.$snapshot->price_currency : 'fără preț' }}</span>@if($snapshot->location)<span class="text-dim">{{ $snapshot->location }}</span>@endif @if($snapshot->seller_name)<span class="text-dim">{{ $snapshot->seller_name }}</span>@endif</div>@if($snapshot->title && $snapshot->title !== $currentDeal->title)<p class="mt-1 text-sm text-dim">{{ $snapshot->title }}</p>@endif</div>
                                    </article>
                                @endforeach
                            </div>
                        </section>
                    @endif
                </div>

                <aside class="space-y-8">
                    @if(count($imageUrls) > 0)
                        <section class="border border-hairline bg-bench p-4" aria-labelledby="media-heading"><p class="placard text-[0.6rem]">Media</p><h3 id="media-heading" class="sr-only">Imagini OLX</h3><div class="mt-3 grid grid-cols-2 gap-px bg-hairline">@foreach(array_slice($imageUrls, 0, 6) as $imageUrl)<a href="{{ $imageUrl }}" target="_blank" rel="noopener" class="aspect-square bg-[#06080a]"><img src="{{ $imageUrl }}" alt="Imagine anunț" class="h-full w-full object-cover" onerror="this.remove()"></a>@endforeach</div>@if(count($imageUrls) > 6)<p class="mt-2 font-mono text-[0.65rem] text-dim/70">+{{ count($imageUrls) - 6 }} imagini</p>@endif</section>
                    @endif

                    <section class="border border-hairline bg-bench px-5 py-5" aria-labelledby="metadata-heading">
                        <p class="placard text-[0.6rem]">Date anunț</p>
                        <h3 id="metadata-heading" class="sr-only">Date anunț</h3>
                        <dl class="mt-4 space-y-3 font-mono text-[0.7rem] tabular-nums">
                            <div class="flex justify-between gap-4"><dt class="text-dim">Găsit</dt><dd class="text-right text-[#eaf4f6]">{{ $deal->created_at->format('d M Y, H:i') }}</dd></div>
                            @if($currentDeal->posted_at)
                                <div class="flex justify-between gap-4"><dt class="text-dim">Publicat</dt><dd class="text-right text-[#eaf4f6]">{{ $currentDeal->posted_at->format('d M Y, H:i') }}</dd></div>
                            @endif
                            @if($currentDeal->location)
                                <div class="flex justify-between gap-4"><dt class="text-dim">Locație</dt><dd class="text-right text-[#eaf4f6]">{{ $currentDeal->location }}</dd></div>
                            @endif
                            @if($currentDeal->seller_name)
                                <div class="flex justify-between gap-4"><dt class="text-dim">Vânzător</dt><dd class="text-right text-[#eaf4f6]">{{ $currentDeal->seller_name }}</dd></div>
                            @endif
                            @if($currentDeal->seller_url)
                                <div><a href="{{ $currentDeal->seller_url }}" target="_blank" rel="noopener" class="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.6rem]">Profil vânzător &#8599;</a></div>
                            @endif
                        </dl>
                    </section>

                    <section class="border border-hairline bg-bench px-5 py-5" aria-labelledby="search-heading"><p class="placard text-[0.6rem]">Căutare asociată</p><h3 id="search-heading" class="mt-2 font-sans font-semibold text-[#eaf4f6]">{{ $deal->huntedDeal->search_term }}</h3><p class="mt-2 font-mono text-[0.7rem] text-{{ $deal->huntedDeal->is_active ? 'em-green' : 'dim' }}">{{ $deal->huntedDeal->is_active ? 'Activă' : 'În pauză' }}</p><a href="{{ route('hunted-deals.show', $deal->huntedDeal) }}" class="rail-link focus-ring mt-4 rounded-sm border-b pb-0.5 text-[0.6rem]">Vezi căutarea &rarr;</a></section>
                </aside>
            </div>
        </div>
    </div>
</x-app-layout>
