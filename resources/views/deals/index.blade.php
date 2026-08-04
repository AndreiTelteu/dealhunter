<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="placard mb-1.5 text-[0.6rem]">Registru anunțuri</p>
                <h2 class="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">Anunțuri găsite</h2>
                <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {{ $deals->total() }} {{ $deals->total() === 1 ? 'anunț' : 'anunțuri' }} în registru
                </p>
            </div>
            <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]">
                Căutările mele
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-label="Filtre anunțuri">
                <form method="GET" action="{{ route('deals.index') }}" class="border border-hairline bg-bench px-5 py-5">
                    @if(request('hunted_deal'))
                        <input type="hidden" name="hunted_deal" value="{{ request('hunted_deal') }}">
                    @endif

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
                        <a href="{{ route('deals.index', request('hunted_deal') ? ['hunted_deal' => request('hunted_deal')] : []) }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Resetează</a>
                        <button type="submit" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Aplică</button>
                    </div>
                </form>
            </section>

            <section aria-labelledby="deals-ledger-heading">
                <div class="mb-4 flex flex-wrap items-end justify-between gap-3">
                    <div>
                        <p class="placard text-[0.6rem]">Semnal primit</p>
                        <h3 id="deals-ledger-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">Lista anunțurilor</h3>
                    </div>
                    @if(request('hunted_deal'))
                        <span class="font-mono text-[0.65rem] tabular-nums text-dim/70">filtrat după căutare</span>
                    @endif
                </div>

                @if($deals->count() > 0)
                    <div class="border-t border-hairline">
                        @foreach($deals as $deal)
                            <article class="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
                                <div class="grid gap-4 lg:grid-cols-[minmax(0,1fr)_11rem] lg:items-center lg:gap-8">
                                    <div class="min-w-0">
                                        <div class="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                            <x-favorite-button :deal="$deal" />
                                            <a href="{{ route('deals.show', $deal) }}" class="focus-ring rounded-sm font-sans font-semibold text-[#eaf4f6] transition-colors group-hover:text-beam">
                                                {{ Str::limit($deal->title, 100) }}
                                            </a>
                                            @if($deal->matches_intent)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green"><span class="spec-line h-1 w-1 bg-[#7dffa8]" aria-hidden="true"></span>Potrivit @if($deal->intent_score !== null) {{ $deal->intent_score }}% @endif</span>
                                            @endif
                                            @if($deal->likely_working)
                                                <span class="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green"><span class="spec-line h-1 w-1 bg-[#7dffa8]" aria-hidden="true"></span>Funcțional</span>
                                            @endif
                                            @if($deal->created_at >= now()->subDay())
                                                <span class="font-mono text-[0.6rem] uppercase text-em-amber">Nou</span>
                                            @endif
                                        </div>
                                        @if($deal->description)
                                            <p class="mt-1 text-sm text-dim" style="max-width:68ch">{{ Str::limit($deal->description, 150) }}</p>
                                        @endif
                                        <p class="mt-2 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            @if($deal->location){{ $deal->location }} &middot; @endif
                                            văzut {{ $deal->last_seen_at?->diffForHumans() ?? 'fără dată' }} &middot;
                                            {{ $deal->snapshots_count }} {{ $deal->snapshots_count === 1 ? 'instantaneu' : 'instantanee' }} &middot;
                                            căutare: <a href="{{ route('hunted-deals.show', $deal->huntedDeal) }}" class="text-beam hover:underline">{{ $deal->huntedDeal->search_term }}</a>
                                        </p>
                                    </div>
                                    <div class="flex items-end justify-between gap-5 lg:flex-col lg:items-end lg:gap-3">
                                        <div class="text-right">
                                            <p class="placard text-[0.55rem]">Preț curent</p>
                                            <p class="mt-1 font-mono text-lg font-bold tabular-nums text-[#eaf4f6]">
                                                @if($deal->latestSnapshot?->price_amount ?? $deal->price_amount)
                                                    {{ number_format($deal->latestSnapshot?->price_amount ?? $deal->price_amount, 0, ',', '.') }} {{ $deal->latestSnapshot?->price_currency ?? $deal->price_currency }}
                                                @else
                                                    <span class="text-sm font-normal text-dim">Fără preț</span>
                                                @endif
                                            </p>
                                        </div>
                                        <div class="flex shrink-0 items-center gap-4">
                                            <a href="{{ route('deals.show', $deal) }}" class="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]">Detalii</a>
                                            @if($deal->url)<a href="{{ $deal->url }}" target="_blank" rel="noopener" class="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]">OLX &#8599;</a>@endif
                                        </div>
                                    </div>
                                </div>
                            </article>
                        @endforeach
                    </div>

                    @if($deals->hasPages())
                        <div class="mt-6 border-t border-hairline pt-4 font-mono text-sm text-dim">{{ $deals->links() }}</div>
                    @endif
                @else
                    <div class="relative border border-hairline graticule">
                        <div class="px-6 py-14 text-center sm:py-16">
                            <div class="relative mx-auto mb-8 h-16 max-w-md" aria-hidden="true"><div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div><div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div></div>
                            @if(request()->hasAny(['search', 'price_drops', 'new_items', 'matches_intent', 'likely_working']))
                                <h3 class="font-sans text-lg font-bold text-[#eaf4f6]">Niciun anunț nu corespunde</h3>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">Schimbă termenul sau filtrele și încearcă din nou.</p>
                                <a href="{{ route('deals.index', request('hunted_deal') ? ['hunted_deal' => request('hunted_deal')] : []) }}" class="beamkey focus-ring mt-7 rounded-sm px-6 py-3 text-[0.7rem]">Resetează filtrele</a>
                            @else
                                <h3 class="font-sans text-lg font-bold text-[#eaf4f6]">Niciun anunț încă</h3>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">Adaugă o căutare urmărită. Anunțurile găsite apar aici.</p>
                                <a href="{{ route('hunted-deals.create') }}" class="beamkey beamkey-armed focus-ring mt-7 rounded-sm px-6 py-3 text-[0.7rem]">+ Căutare nouă</a>
                            @endif
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
