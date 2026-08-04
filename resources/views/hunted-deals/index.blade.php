<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p class="placard text-[0.6rem] mb-1.5">Căutări urmărite</p>
                <h2 class="font-sans font-bold text-xl sm:text-2xl text-[#eaf4f6] leading-tight">
                    Toate căutările tale
                </h2>
                <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {{ $huntedDeals->total() }} {{ $huntedDeals->total() == 1 ? 'căutare urmărită' : 'căutări urmărite' }}
                </p>
            </div>
            <a href="{{ route('hunted-deals.create') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem] shrink-0">
                + Căutare nouă
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-8">

            <!-- ============ FILTERS ============ -->
            <section aria-label="Filtre">
                <form method="GET" action="{{ route('hunted-deals.index') }}" class="border border-hairline bg-bench px-5 py-5">
                    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                        <div>
                            <x-input-label for="search" value="Caută" />
                            <x-text-input id="search" name="search" type="text" class="block mt-2 w-full"
                                :value="request('search')" placeholder="Termen sau notiță…" />
                        </div>
                        <div>
                            <x-input-label for="filter" value="Stare" />
                            <select id="filter" name="filter"
                                class="block mt-2 w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                                <option value="">Toate</option>
                                <option value="active" @selected(request('filter') === 'active')>Doar active</option>
                                <option value="inactive" @selected(request('filter') === 'inactive')>Doar în pauză</option>
                                <option value="never_crawled" @selected(request('filter') === 'never_crawled')>Neverificate</option>
                                <option value="recently_crawled" @selected(request('filter') === 'recently_crawled')>Verificate în 24h</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="sort" value="Sortează după" />
                            <select id="sort" name="sort"
                                class="block mt-2 w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                                <option value="updated_at" @selected(request('sort', 'updated_at') === 'updated_at')>Ultima actualizare</option>
                                <option value="created_at" @selected(request('sort') === 'created_at')>Data creării</option>
                                <option value="search_term" @selected(request('sort') === 'search_term')>Termen căutat</option>
                                <option value="last_crawled_at" @selected(request('sort') === 'last_crawled_at')>Ultima verificare</option>
                                <option value="deals_count" @selected(request('sort') === 'deals_count')>Anunțuri găsite</option>
                            </select>
                        </div>
                        <div>
                            <x-input-label for="direction" value="Ordine" />
                            <select id="direction" name="direction"
                                class="block mt-2 w-full rounded-sm border-hairline bg-[#06080a] text-[#eaf4f6] shadow-none focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30">
                                <option value="desc" @selected(request('direction', 'desc') === 'desc')>Descrescător</option>
                                <option value="asc" @selected(request('direction') === 'asc')>Crescător</option>
                            </select>
                        </div>
                    </div>
                    <div class="mt-5 flex flex-wrap items-center gap-2.5">
                        <x-primary-button class="text-[0.65rem]">Aplică filtrele</x-primary-button>
                        <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Reinițializează</a>
                    </div>
                </form>
            </section>

            <!-- ============ LEDGER ============ -->
            <section aria-labelledby="ledger-heading">
                <h3 id="ledger-heading" class="sr-only">Lista căutărilor urmărite</h3>

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
                                            <span class="inline-flex items-center font-mono text-[0.6rem] uppercase text-beam">
                                                {{ $huntedDeal->deals_count }} {{ $huntedDeal->deals_count == 1 ? 'anunț' : 'anunțuri' }}
                                            </span>
                                        </div>
                                        @if($huntedDeal->notes)
                                            <p class="mt-1 text-sm text-dim break-words" style="max-width:68ch">{{ Str::limit($huntedDeal->notes, 140) }}</p>
                                        @endif
                                        <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            Creată {{ $huntedDeal->created_at->format('d M Y') }}
                                            &middot;
                                            Actualizată {{ $huntedDeal->updated_at->diffForHumans() }}
                                            &middot;
                                            @if($huntedDeal->last_crawled_at)
                                                Verificată {{ $huntedDeal->last_crawled_at->diffForHumans() }}
                                            @else
                                                <span class="text-em-amber">Neverificată</span>
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

                    @if($huntedDeals->hasPages())
                        <div class="mt-6">
                            {{ $huntedDeals->links() }}
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
                            @if(request()->hasAny(['search', 'filter']))
                                <h4 class="font-sans font-bold text-lg text-[#eaf4f6]">Nicio căutare nu se potrivește filtrelor</h4>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">
                                    Încearcă să schimbi termenul căutat sau filtrul de stare.
                                </p>
                                <div class="mt-7">
                                    <a href="{{ route('hunted-deals.index') }}" class="beamkey focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                                        Reinițializează filtrele
                                    </a>
                                </div>
                            @else
                                <h4 class="font-sans font-bold text-lg text-[#eaf4f6]">Nicio căutare urmărită încă</h4>
                                <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">
                                    Salvează prima căutare — de exemplu „iPhone 13" — și o verificăm automat pe OLX România. Anunțurile noi și schimbările de preț apar aici.
                                </p>
                                <div class="mt-7">
                                    <a href="{{ route('hunted-deals.create') }}" class="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                                        + Adaugă prima căutare
                                    </a>
                                </div>
                            @endif
                        </div>
                    </div>
                @endif
            </section>

        </div>
    </div>
</x-app-layout>
