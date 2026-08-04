<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p class="placard mb-1.5 text-[0.6rem]">Selecție personală</p>
                <h2 class="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">Anunțuri favorite</h2>
                <p class="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {{ $favorites->total() }} {{ $favorites->total() === 1 ? 'anunț favorit' : 'anunțuri favorite' }}
                </p>
            </div>
            <a href="{{ route('deals.index') }}" class="beamkey focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]">
                Toate anunțurile
            </a>
        </div>
    </x-slot>

    <div class="py-8 sm:py-10">
        <div class="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
            <section aria-labelledby="favorites-ledger-heading">
                <div class="mb-4">
                    <p class="placard text-[0.6rem]">Marcat cu inimioară</p>
                    <h3 id="favorites-ledger-heading" class="mt-1 font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">Lista favoritelor</h3>
                </div>

                @if($favorites->count() > 0)
                    <div class="border-t border-hairline">
                        @foreach($favorites as $favorite)
                            @php($deal = $favorite->deal)
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
                                        </div>
                                        @if($deal->description)
                                            <p class="mt-1 text-sm text-dim" style="max-width:68ch">{{ Str::limit($deal->description, 150) }}</p>
                                        @endif
                                        <p class="mt-2 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                            @if($deal->location){{ $deal->location }} &middot; @endif
                                            adăugat la favorite {{ $favorite->created_at->diffForHumans() }} &middot;
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

                    @if($favorites->hasPages())
                        <div class="mt-6 border-t border-hairline pt-4 font-mono text-sm text-dim">{{ $favorites->links() }}</div>
                    @endif
                @else
                    <div class="relative border border-hairline graticule">
                        <div class="px-6 py-14 text-center sm:py-16">
                            <div class="relative mx-auto mb-8 h-16 max-w-md" aria-hidden="true"><div class="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div><div class="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div></div>
                            <h3 class="font-sans text-lg font-bold text-[#eaf4f6]">Nicio favorită încă</h3>
                            <p class="mx-auto mt-2 text-sm text-dim" style="max-width:52ch">Apasă inimioara de lângă un anunț pentru a-l marca aici.</p>
                            <a href="{{ route('deals.index') }}" class="beamkey beamkey-armed focus-ring mt-7 rounded-sm px-6 py-3 text-[0.7rem]">Vezi anunțurile</a>
                        </div>
                    </div>
                @endif
            </section>
        </div>
    </div>
</x-app-layout>
