import type { ReactElement } from 'react';
import { Link } from '@inertiajs/react';
import type { PaginationLink, PaginationMeta } from '../types';

interface PaginationProps {
    /** Meta subset of the Laravel paginator (currentPage, lastPage, ...). */
    meta: PaginationMeta;
    /** Link rows as produced by `AbstractPaginator::linkCollection()`. */
    links: PaginationLink[];
    /** Noun used in the results summary, e.g. "Rezultate" / "Anunțuri". */
    entityLabel?: string;
    className?: string;
}

const FRAME =
    'inline-flex min-h-9 min-w-9 items-center justify-center border border-hairline';

/** Decode paginator labels ("&hellip;", "&laquo;" ...) for React rendering. */
function decodeLabel(value: string): string {
    if (!value.includes('&')) {
        return value;
    }

    const doc = new DOMParser().parseFromString(value, 'text/html');

    return doc.body.textContent ?? value;
}

function ChevronLeft(): ReactElement {
    return (
        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
                fillRule="evenodd"
                d="M12.707 5.293a1 1 0 0 1 0 1.414L9.414 10l3.293 3.293a1 1 0 0 1-1.414 1.414l-4-4a1 1 0 0 1 0-1.414l4-4a1 1 0 0 1 1.414 0Z"
                clipRule="evenodd"
            />
        </svg>
    );
}

function ChevronRight(): ReactElement {
    return (
        <svg className="h-4 w-4" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
            <path
                fillRule="evenodd"
                d="M7.293 14.707a1 1 0 0 1 0-1.414L10.586 10 7.293 6.707a1 1 0 0 1 1.414-1.414l4 4a1 1 0 0 1 0 1.414l-4 4a1 1 0 0 1-1.414 0Z"
                clipRule="evenodd"
            />
        </svg>
    );
}

/**
 * React port of resources/views/vendor/pagination/tailwind.blade.php.
 *
 * Consumes the serialized Laravel paginator (`linkCollection()` rows + meta
 * subset). Every link URL already carries the current filter/sort query
 * string (controllers call `withQueryString()`), so plain Inertia `Link`
 * visits preserve filter state with no extra work.
 */
export default function Pagination({
    meta,
    links,
    entityLabel = 'Rezultate',
    className = '',
}: PaginationProps): ReactElement | null {
    if (meta.lastPage <= 1) {
        return null;
    }

    const previous = meta.currentPage > 1 ? links[0] : null;
    const next = meta.currentPage < meta.lastPage ? links[links.length - 1] : null;
    const pages = links.slice(1, -1);

    return (
        <nav
            role="navigation"
            aria-label="Navigare prin pagini"
            className={`border border-hairline bg-transparent px-3 py-3 sm:px-4 ${className}`.trim()}
        >
            {/* Mobile: previous / next + current-page readout */}
            <div className="flex items-center justify-between gap-3 sm:hidden">
                {previous?.url ? (
                    <Link
                        href={previous.url}
                        rel="prev"
                        preserveScroll
                        aria-label="Pagina anterioară"
                        className="focus-ring inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim transition-colors hover:border-[#59e3ff]/50 hover:text-beam focus-visible:border-[#59e3ff]"
                    >
                        Anterior
                    </Link>
                ) : (
                    <span
                        aria-disabled="true"
                        className="inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim/45"
                    >
                        Anterior
                    </span>
                )}

                <span
                    className="font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]"
                    aria-current="page"
                >
                    {meta.currentPage} / {meta.lastPage}
                </span>

                {next?.url ? (
                    <Link
                        href={next.url}
                        rel="next"
                        preserveScroll
                        aria-label="Pagina următoare"
                        className="focus-ring inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim transition-colors hover:border-[#59e3ff]/50 hover:text-beam focus-visible:border-[#59e3ff]"
                    >
                        Următorul
                    </Link>
                ) : (
                    <span
                        aria-disabled="true"
                        className="inline-flex min-h-9 items-center border border-hairline px-3 font-mono text-[0.65rem] uppercase text-dim/45"
                    >
                        Următorul
                    </span>
                )}
            </div>

            {/* Desktop: results summary + full page controls */}
            <div className="hidden items-center justify-between gap-6 sm:flex">
                <p className="font-mono text-[0.7rem] tabular-nums text-dim">
                    {meta.from !== null && meta.to !== null ? (
                        <>
                            {entityLabel}{' '}
                            <span className="text-[#eaf4f6]">
                                {meta.from}-{meta.to}
                            </span>{' '}
                            din <span className="text-[#eaf4f6]">{meta.total}</span>
                        </>
                    ) : (
                        <>
                            <span className="text-[#eaf4f6]">{meta.total}</span> {entityLabel.toLowerCase()}
                        </>
                    )}
                </p>

                <div
                    className="inline-flex items-stretch font-mono text-[0.7rem] tabular-nums"
                    role="list"
                >
                    {previous?.url ? (
                        <Link
                            href={previous.url}
                            rel="prev"
                            preserveScroll
                            role="listitem"
                            aria-label="Pagina anterioară"
                            className={`focus-ring -mr-px ${FRAME} text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]`}
                        >
                            <ChevronLeft />
                        </Link>
                    ) : (
                        <span
                            role="listitem"
                            aria-disabled="true"
                            aria-label="Pagina anterioară"
                            className={`${FRAME} text-dim/45`}
                        >
                            <ChevronLeft />
                        </span>
                    )}

                    {pages.map((link, index) => {
                        if (link.active) {
                            return (
                                <span
                                    key={`${link.label}-${index}`}
                                    role="listitem"
                                    aria-current="page"
                                    aria-label={`Pagina ${link.label}, pagina curentă`}
                                    className={`relative z-10 -mr-px inline-flex min-h-9 min-w-9 items-center justify-center border border-[#59e3ff]/70 bg-[#59e3ff]/10 text-beam shadow-[0_3px_7px_rgba(0,0,0,0.7),0_0_10px_rgba(89,227,255,0.18)]`}
                                >
                                    {link.label}
                                </span>
                            );
                        }

                        if (link.url === null) {
                            return (
                                <span
                                    key={`${link.label}-${index}`}
                                    role="listitem"
                                    aria-disabled="true"
                                    className={`${FRAME} -mr-px text-dim/60`}
                                >
                                    {decodeLabel(link.label)}
                                </span>
                            );
                        }

                        return (
                            <Link
                                key={`${link.label}-${index}`}
                                href={link.url as string}
                                role="listitem"
                                preserveScroll
                                aria-label={`Mergi la pagina ${link.label}`}
                                className={`focus-ring -mr-px ${FRAME} text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:bg-[#59e3ff]/5 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]`}
                            >
                                {link.label}
                            </Link>
                        );
                    })}

                    {next?.url ? (
                        <Link
                            href={next.url}
                            rel="next"
                            preserveScroll
                            role="listitem"
                            aria-label="Pagina următoare"
                            className={`focus-ring ${FRAME} text-dim transition-colors hover:z-10 hover:border-[#59e3ff]/50 hover:text-beam focus:z-10 focus-visible:border-[#59e3ff]`}
                        >
                            <ChevronRight />
                        </Link>
                    ) : (
                        <span
                            role="listitem"
                            aria-disabled="true"
                            aria-label="Pagina următoare"
                            className={`${FRAME} text-dim/45`}
                        >
                            <ChevronRight />
                        </span>
                    )}
                </div>
            </div>
        </nav>
    );
}
