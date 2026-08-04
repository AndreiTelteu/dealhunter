<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between" x-data="{
            autoRefresh: false,
            refreshing: false,
            refreshInterval: null,
            toggleAutoRefresh() {
                this.autoRefresh = ! this.autoRefresh;
                if (this.autoRefresh) {
                    this.refreshInterval = setInterval(() => window.location.reload(), 300000);
                } else {
                    clearInterval(this.refreshInterval);
                }
            }
        }">
            <div>
                <p class="placard text-[0.6rem] mb-1.5">Panou &middot; {{ Auth::user()->name }}</p>
                <h2 class="font-sans font-bold text-xl sm:text-2xl text-[#eaf4f6] leading-tight">
                    Anunțurile tale, dintr-o privire
                </h2>
            </div>
            <div class="flex flex-wrap items-center gap-2.5">
                <button type="button"
                    @click="toggleAutoRefresh()"
                    class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                    :class="{ 'beamkey-armed': autoRefresh }"
                    :aria-pressed="autoRefresh.toString()">
                    <span class="inline-block h-1.5 w-1.5 rounded-full"
                          :class="autoRefresh ? 'bg-[#7dffa8]' : 'bg-[#1c242a]'"
                          :style="autoRefresh ? 'box-shadow: 0 0 8px rgba(125,255,168,0.6);' : ''"
                          aria-hidden="true"></span>
                    <span x-text="autoRefresh ? 'Auto-refresh pornit' : 'Auto-refresh oprit'">Auto-refresh oprit</span>
                </button>
                <button type="button"
                    @click="refreshing = true; window.location.reload()"
                    class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                    :class="{ 'opacity-50 pointer-events-none': refreshing }"
                    :disabled="refreshing">
                    <svg class="h-3.5 w-3.5" :class="{ 'animate-spin': refreshing }" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"></path>
                    </svg>
                    <span x-text="refreshing ? 'Se reîncarcă…' : 'Reîncarcă'">Reîncarcă</span>
                </button>
                <a href="{{ route('hunted-deals.create') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    + Căutare nouă
                </a>
            </div>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8 sm:space-y-10">

            <!-- ============ SPECTRAL READOUTS ============ -->
            <section aria-label="Statistici">
                <div class="grid grid-cols-2 lg:grid-cols-4 gap-3 sm:gap-4">
                    <x-stat-card
                        title="Căutări urmărite"
                        :value="$huntedDealsCount"
                        color="cyan"
                        :href="route('hunted-deals.index')"
                    />
                    <x-stat-card
                        title="Căutări active"
                        :value="$activeHuntedDealsCount"
                        color="green"
                        :href="route('hunted-deals.index') . '?filter=active'"
                    />
                    <x-stat-card
                        title="Anunțuri găsite"
                        :value="$totalDealsCount"
                        color="cyan"
                        :href="route('deals.index')"
                    />
                    <x-stat-card
                        title="Noi în 24 de ore"
                        :value="$newDealsCount"
                        :color="$newDealsCount > 0 ? 'amber' : 'dim'"
                        :href="route('deals.index') . '?new_items=1'"
                    />
                </div>
            </section>

            <!-- ============ HUNTED SEARCHES LEDGER ============ -->
            <section aria-labelledby="hunted-heading">
                <div class="flex items-end justify-between flex-wrap gap-3 mb-4">
                    <div>
                        <h3 id="hunted-heading" class="font-sans font-bold text-lg sm:text-xl text-[#eaf4f6]">Căutările tale urmărite</h3>
                        <p class="mt-1 text-sm text-dim" style="max-width:56ch">Fiecare căutare salvată este verificată automat pe OLX România.</p>
                    </div>
                    <a href="{{ route('hunted-deals.index') }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">
                        Toate căutările &rarr;
                    </a>
                </div>

                @if($huntedDeals->count() > 0)
                    <div class="border-t border-hairline">
                        @foreach($huntedDeals as $huntedDeal)
                            <div class="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="focus-ring rounded-sm font-sans font-semibold text-[#eaf4f6] group-hover:text-beam transition-colors break-words">
                                                {{ $huntedDeal->search_term }}
                                            </a>
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
                                        </div>
                                        @if($huntedDeal->notes)
                                            <p class="mt-1 text-sm text-dim break-words" style="max-width:68ch">{{ Str::limit($huntedDeal->notes, 100) }}</p>
                                        @endif
                                        <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            {{ $huntedDeal->deals_count ?? 0 }} {{ ($huntedDeal->deals_count ?? 0) == 1 ? 'anunț găsit' : 'anunțuri găsite' }}
                                            &middot;
                                            @if($huntedDeal->last_crawled_at)
                                                ultima verificare {{ $huntedDeal->last_crawled_at->diffForHumans() }}
                                            @else
                                                neverificată încă
                                            @endif
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4 sm:gap-5">
                                        <a href="{{ route('hunted-deals.show', $huntedDeal) }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">Detalii</a>
                                        <a href="{{ route('hunted-deals.edit', $huntedDeal) }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">Editează</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <!-- ============ EMPTY STATE: THE PARKED BEAM ============ -->
                    <div class="relative border border-hairline graticule">
                        <div class="px-6 py-14 sm:py-16 text-center">
                            <div class="relative mx-auto mb-8 h-16 max-w-md" aria-hidden="true">
                                <div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                                <div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div>
                            </div>
                            <h4 class="font-sans font-bold text-lg text-[#eaf4f6]">Nicio căutare urmărită încă</h4>
                            <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">
                                Salvează prima căutare — de exemplu „iPhone 13" — și o verificăm automat pe OLX România. Anunțurile noi și schimbările de preț apar aici.
                            </p>
                            <div class="mt-7">
                                <a href="{{ route('hunted-deals.create') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                                    + Adaugă prima căutare
                                </a>
                            </div>
                        </div>
                    </div>
                @endif
            </section>

            <!-- ============ RECENT LISTINGS LEDGER ============ -->
            <section aria-labelledby="recent-heading">
                <div class="flex items-end justify-between flex-wrap gap-3 mb-4">
                    <div>
                        <h3 id="recent-heading" class="font-sans font-bold text-lg sm:text-xl text-[#eaf4f6]">Anunțuri recente</h3>
                        <p class="mt-1 text-sm text-dim" style="max-width:56ch">Ultimele anunțuri găsite de căutările tale.</p>
                    </div>
                    <a href="{{ route('deals.index') }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">
                        Toate anunțurile &rarr;
                    </a>
                </div>

                @if($recentDeals->count() > 0)
                    <div class="border-t border-hairline">
                        @foreach($recentDeals as $deal)
                            <div class="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
                                <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <x-favorite-button :deal="$deal" />
                                            <a href="{{ route('deals.show', $deal) }}" class="focus-ring rounded-sm font-sans font-semibold text-[#eaf4f6] group-hover:text-beam transition-colors break-words">
                                                {{ Str::limit($deal->title, 60) }}
                                            </a>
                                            @if($deal->matches_intent)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                                    <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                                                    Potrivit
                                                </span>
                                            @endif
                                            @if($deal->likely_working)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                                    <span class="inline-block h-1 w-1 rounded-full bg-[#7dffa8]" style="box-shadow: 0 0 6px rgba(125,255,168,0.6);" aria-hidden="true"></span>
                                                    Funcțional
                                                </span>
                                            @endif
                                        </div>
                                        <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            @if($deal->price_amount)
                                                <span class="text-[#eaf4f6]">{{ number_format($deal->price_amount, 0) }} {{ $deal->price_currency }}</span> &middot;
                                            @endif
                                            @if($deal->location)
                                                {{ $deal->location }} &middot;
                                            @endif
                                            {{ $deal->created_at->diffForHumans() }}
                                        </p>
                                        <p class="mt-1 text-sm text-dim break-words">
                                            Căutare: <span class="text-dim">{{ $deal->huntedDeal->search_term }}</span>
                                        </p>
                                    </div>
                                    <div class="flex shrink-0 items-center gap-4 sm:gap-5">
                                        <a href="{{ route('deals.show', $deal) }}" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">Detalii</a>
                                        <a href="{{ $deal->url }}" target="_blank" rel="noopener" class="rail-link focus-ring rounded-sm pb-0.5 border-b text-[0.65rem]">OLX &#8599;</a>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    <div class="border border-hairline px-6 py-10 text-center">
                        <p class="font-sans font-semibold text-[#eaf4f6]">Niciun anunț găsit încă</p>
                        <p class="mx-auto mt-1.5 text-sm text-dim" style="max-width:52ch">
                            Adaugă căutări urmărite și așteaptă ca verificarea automată să adune anunțuri — ele apar aici.
                        </p>
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
