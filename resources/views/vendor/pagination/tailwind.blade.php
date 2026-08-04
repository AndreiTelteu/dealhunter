@if ($paginator->hasPages())
    <nav role="navigation" aria-label="Navigare prin pagini" class="border border-hairline bg-transparent px-3 py-3 sm:px-4">
        <div class="flex items-center justify-between gap-3 sm:hidden">
            @if ($paginator->onFirstPage())
                <span aria-disabled="true" class="inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim/45">
                    Anterior
                </span>
            @else
                <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="focus-ring inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim transition-colors hover:border-[#59e3ff]/50 hover:text-beam focus-visible:border-[#59e3ff]" aria-label="Pagina anterioară">
                    Anterior
                </a>
            @endif

            <span class="font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]" aria-current="page">
                {{ $paginator->currentPage() }} / {{ $paginator->lastPage() }}
            </span>

            @if ($paginator->hasMorePages())
                <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="focus-ring inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim transition-colors hover:border-[#59e3ff]/50 hover:text-beam focus-visible:border-[#59e3ff]" aria-label="Pagina următoare">
                    Următorul
                </a>
            @else
                <span aria-disabled="true" class="inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim/45">
                    Următorul
                </span>
            @endif
        </div>

        <div class="hidden items-center justify-between gap-6 sm:flex">
            <p class="font-mono text-[0.7rem] tabular-nums text-dim">
                @if ($paginator->firstItem())
                    Rezultate <span class="text-[#eaf4f6]">{{ $paginator->firstItem() }}-{{ $paginator->lastItem() }}</span> din <span class="text-[#eaf4f6]">{{ $paginator->total() }}</span>
                @else
                    <span class="text-[#eaf4f6]">{{ $paginator->count() }}</span> rezultate
                @endif
            </p>

            <div class="inline-flex items-stretch font-mono text-[0.7rem] tabular-nums rtl:flex-row-reverse" role="list">
                @if ($paginator->onFirstPage())
                    <span aria-disabled="true" aria-label="Pagina anterioară" class="inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim/45" role="listitem">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 0 1 0 1.414L9.414 10l3.293 3.293a1 1 0 0 1-1.414 1.414l-4-4a1 1 0 0 1 0-1.414l4-4a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" /></svg>
                    </span>
                @else
                    <a href="{{ $paginator->previousPageUrl() }}" rel="prev" class="focus-ring -mr-px inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]" aria-label="Pagina anterioară" role="listitem">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M12.707 5.293a1 1 0 0 1 0 1.414L9.414 10l3.293 3.293a1 1 0 0 1-1.414 1.414l-4-4a1 1 0 0 1 0-1.414l4-4a1 1 0 0 1 1.414 0Z" clip-rule="evenodd" /></svg>
                    </a>
                @endif

                @foreach ($elements as $element)
                    @if (is_string($element))
                        <span aria-disabled="true" class="-mr-px inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim/60" role="listitem">{{ $element }}</span>
                    @endif

                    @if (is_array($element))
                        @foreach ($element as $page => $url)
                            @if ($page == $paginator->currentPage())
                                <span aria-current="page" aria-label="Pagina {{ $page }}, pagina curentă" class="relative z-10 -mr-px inline-flex min-h-9 min-w-9 items-center justify-center border border-[#59e3ff]/70 bg-[#59e3ff]/10 text-beam shadow-[0_3px_7px_rgba(0,0,0,0.7),0_0_10px_rgba(89,227,255,0.18)]" role="listitem">{{ $page }}</span>
                            @else
                                <a href="{{ $url }}" class="focus-ring -mr-px inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:bg-[#59e3ff]/5 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]" aria-label="Mergi la pagina {{ $page }}" role="listitem">{{ $page }}</a>
                            @endif
                        @endforeach
                    @endif
                @endforeach

                @if ($paginator->hasMorePages())
                    <a href="{{ $paginator->nextPageUrl() }}" rel="next" class="focus-ring inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]" aria-label="Pagina următoare" role="listitem">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" /></svg>
                    </a>
                @else
                    <span aria-disabled="true" aria-label="Pagina următoare" class="inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline text-dim/45" role="listitem">
                        <svg class="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true"><path fill-rule="evenodd" d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z" clip-rule="evenodd" /></svg>
                    </span>
                @endif
            </div>
        </div>
    </nav>
@endif
