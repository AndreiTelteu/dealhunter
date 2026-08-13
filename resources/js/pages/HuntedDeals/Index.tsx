import { useState, type FormEvent, type ReactElement } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import Pagination from '../../components/Pagination';
import { EmptyState } from '../../components/ui/EmptyState';
import { formatNumber } from '../../lib/format';
import type { HuntedDeal, Paginated, SharedPageProps } from '../../types';

interface HuntedDealsIndexLinks {
    index: string;
    create: string;
}

interface HuntedDealsIndexFilters {
    search: string | null;
    filter: string | null;
    sort: string;
    direction: string;
    hasActiveFilters: boolean;
}

interface HuntedDealsIndexPageProps extends SharedPageProps {
    huntedDeals: Paginated<HuntedDeal>;
    filters: HuntedDealsIndexFilters;
    links: HuntedDealsIndexLinks;
}

const FILTER_OPTIONS = [
    { value: '', label: 'Toate' },
    { value: 'active', label: 'Doar active' },
    { value: 'inactive', label: 'Doar în pauză' },
    { value: 'never_crawled', label: 'Neverificate' },
    { value: 'recently_crawled', label: 'Verificate în 24h' },
] as const;

const SORT_OPTIONS = [
    { value: 'updated_at', label: 'Ultima actualizare' },
    { value: 'created_at', label: 'Data creării' },
    { value: 'search_term', label: 'Termen căutat' },
    { value: 'last_crawled_at', label: 'Ultima verificare' },
    { value: 'deals_count', label: 'Anunțuri găsite' },
] as const;

const FIELD_CLASS =
    'mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

export default function HuntedDealsIndex(): ReactElement {
    const { huntedDeals, filters, links } = usePage<HuntedDealsIndexPageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [filter, setFilter] = useState(filters.filter ?? '');
    const [sort, setSort] = useState(filters.sort);
    const [direction, setDirection] = useState(filters.direction);

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(
            links.index,
            {
                ...(search.trim() !== '' ? { search: search } : {}),
                ...(filter !== '' ? { filter } : {}),
                ...(sort !== 'updated_at' ? { sort } : {}),
                ...(direction !== 'desc' ? { direction } : {}),
            },
            { preserveState: false, preserveScroll: true },
        );
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Căutări urmărite</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    Toate căutările tale
                </h2>
                <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {huntedDeals.meta.total} {huntedDeals.meta.total === 1 ? 'căutare urmărită' : 'căutări urmărite'}
                </p>
            </div>
            <Link
                href={links.create}
                className="beamkey beamkey-armed focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]"
            >
                + Căutare nouă
            </Link>
        </div>
    );

    const snapshotReadout = (huntedDeal: HuntedDeal): ReactElement | null => {
        const snapshot = huntedDeal.latestPriceSnapshot;
        if (!snapshot) {
            return null;
        }

        const currency = snapshot.priceCurrency ?? 'RON';

        return (
            <div className="flex items-center gap-3 whitespace-nowrap font-mono text-[0.65rem] tabular-nums">
                <span className="text-dim/70">
                    Min: <span className="text-beam">{snapshot.minPrice !== null ? formatNumber(snapshot.minPrice) : '—'} {currency}</span>
                </span>
                <span className="text-dim/70">
                    Medie: <span className="text-[#eaf4f6]">{snapshot.averagePrice !== null ? formatNumber(snapshot.averagePrice) : '—'} {currency}</span>
                </span>
            </div>
        );
    };

    return (
        <AppLayout title="Căutări urmărite" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section aria-label="Filtre">
                        <form onSubmit={handleSubmit} className="border border-hairline bg-bench px-5 py-5">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                <div>
                                    <label htmlFor="search" className="placard text-[0.6rem]">
                                        Caută
                                    </label>
                                    <input
                                        id="search"
                                        name="search"
                                        type="text"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Termen sau notiță…"
                                        className={FIELD_CLASS}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="filter" className="placard text-[0.6rem]">
                                        Stare
                                    </label>
                                    <select
                                        id="filter"
                                        name="filter"
                                        value={filter}
                                        onChange={(event) => setFilter(event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        {FILTER_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="sort" className="placard text-[0.6rem]">
                                        Sortează după
                                    </label>
                                    <select
                                        id="sort"
                                        name="sort"
                                        value={sort}
                                        onChange={(event) => setSort(event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        {SORT_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="direction" className="placard text-[0.6rem]">
                                        Ordine
                                    </label>
                                    <select
                                        id="direction"
                                        name="direction"
                                        value={direction}
                                        onChange={(event) => setDirection(event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        <option value="desc">Descrescător</option>
                                        <option value="asc">Crescător</option>
                                    </select>
                                </div>
                            </div>
                            <div className="mt-5 flex flex-wrap items-center gap-2.5">
                                <button type="submit" className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                                    Aplică filtrele
                                </button>
                                <Link
                                    href={links.index}
                                    className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                                >
                                    Reinițializează
                                </Link>
                            </div>
                        </form>
                    </section>

                    <section aria-labelledby="ledger-heading">
                        <h3 id="ledger-heading" className="sr-only">
                            Lista căutărilor urmărite
                        </h3>

                        {huntedDeals.data.length > 0 ? (
                            <>
                                <div className="border-t border-hairline">
                                    {huntedDeals.data.map((huntedDeal) => (
                                        <div
                                            key={huntedDeal.id}
                                            className="group border-b border-hairline py-4 transition-colors hover:bg-bench/60"
                                        >
                                            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                                                <div className="min-w-0 flex-1">
                                                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                                                        <Link
                                                            href={huntedDeal.showUrl}
                                                            className="focus-ring rounded-sm font-sans font-semibold text-[#eaf4f6] transition-colors break-words group-hover:text-beam"
                                                        >
                                                            {huntedDeal.searchTerm}
                                                        </Link>
                                                        {huntedDeal.isActive ? (
                                                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                                                <span
                                                                    className="inline-block h-1 w-1 rounded-full bg-[#7dffa8]"
                                                                    style={{ boxShadow: '0 0 6px rgba(125,255,168,0.6)' }}
                                                                    aria-hidden="true"
                                                                />
                                                                Activă
                                                            </span>
                                                        ) : (
                                                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-dim/70">
                                                                <span
                                                                    className="inline-block h-1 w-1 rounded-full bg-[#1c242a]"
                                                                    aria-hidden="true"
                                                                />
                                                                În pauză
                                                            </span>
                                                        )}
                                                        <span className="inline-flex items-center font-mono text-[0.6rem] uppercase text-beam">
                                                            {huntedDeal.dealsCount} {huntedDeal.dealsCount === 1 ? 'anunț' : 'anunțuri'}
                                                        </span>
                                                    </div>
                                                    {huntedDeal.notes && (
                                                        <p className="mt-1 max-w-[68ch] text-sm break-words text-dim">
                                                            {huntedDeal.notes}
                                                        </p>
                                                    )}
                                                    <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                                        Creată {huntedDeal.createdAt}
                                                        {' \u00B7 '}
                                                        Actualizată {huntedDeal.updatedAt}
                                                        {' \u00B7 '}
                                                        {huntedDeal.lastCrawledAt ? (
                                                            <>Verificată {huntedDeal.lastCrawledAt}</>
                                                        ) : (
                                                            <span className="text-em-amber">Neverificată</span>
                                                        )}
                                                    </p>
                                                </div>
                                                <div className="flex shrink-0 flex-wrap items-center gap-x-4 gap-y-2 sm:gap-x-5">
                                                    {snapshotReadout(huntedDeal)}
                                                    <Link
                                                        href={huntedDeal.showUrl}
                                                        className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                                                    >
                                                        Detalii
                                                    </Link>
                                                    <Link
                                                        href={huntedDeal.editUrl}
                                                        className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                                                    >
                                                        Editează
                                                    </Link>
                                                </div>
                                            </div>
                                        </div>
                                    ))}
                                </div>

                                <div className="mt-6">
                                    <Pagination meta={huntedDeals.meta} links={huntedDeals.links} entityLabel="Căutări" />
                                </div>
                            </>
                        ) : filters.hasActiveFilters ? (
                            <EmptyState
                                title="Nicio căutare nu se potrivește filtrelor"
                                description="Încearcă să schimbi termenul căutat sau filtrul de stare."
                                action={
                                    <Link
                                        href={links.index}
                                        className="beamkey focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        Reinițializează filtrele
                                    </Link>
                                }
                            />
                        ) : (
                            <EmptyState
                                title="Nicio căutare urmărită încă"
                                description='Salvează prima căutare — de exemplu „iPhone 13" — și o verificăm automat pe OLX România. Anunțurile noi și schimbările de preț apar aici.'
                                action={
                                    <Link
                                        href={links.create}
                                        className="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        + Adaugă prima căutare
                                    </Link>
                                }
                            />
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
