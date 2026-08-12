import { useState, type ChangeEvent, type FormEvent, type ReactElement } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import Pagination from '../../components/Pagination';
import DealListRow from '../../components/deals/DealListRow';
import DealMediaGallery from '../../components/deals/DealMediaGallery';
import FavoriteButton from '../../components/deals/FavoriteButton';
import { EmptyState } from '../../components/ui/EmptyState';
import type { Deal, DealFilterCounts, Paginated, SharedPageProps } from '../../types';

interface DealsIndexLinks {
    index: string;
    reset: string;
    huntedDealsIndex: string;
    huntedDealsCreate: string;
}

interface DealsIndexFilters {
    search: string | null;
    sort: string;
    direction: string;
    priceDrops: boolean;
    newItems: boolean;
    matchesIntent: boolean;
    likelyWorking: boolean;
    huntedDeal: number | null;
    hasActiveFilters: boolean;
}

interface DealsIndexPageProps extends SharedPageProps {
    deals: Paginated<Deal>;
    filters: DealsIndexFilters;
    filterCounts: DealFilterCounts;
    links: DealsIndexLinks;
}

const SORT_OPTIONS = [
    { value: 'last_seen_at', label: 'Ultima apariție' },
    { value: 'created_at', label: 'Adăugat' },
    { value: 'title', label: 'Titlu' },
    { value: 'price_amount', label: 'Preț' },
    { value: 'location', label: 'Locație' },
] as const;

const FIELD_CLASS =
    'mt-2 block w-full rounded-none border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

export default function DealsIndex(): ReactElement {
    const { deals, filters, filterCounts, links } = usePage<DealsIndexPageProps>().props;

    const [search, setSearch] = useState(filters.search ?? '');
    const [sort, setSort] = useState(filters.sort);
    const [direction, setDirection] = useState(filters.direction);
    const [priceDrops, setPriceDrops] = useState(filters.priceDrops);
    const [newItems, setNewItems] = useState(filters.newItems);
    const [matchesIntent, setMatchesIntent] = useState(filters.matchesIntent);
    const [likelyWorking, setLikelyWorking] = useState(filters.likelyWorking);

    const handleCheckboxChange = (name: string, checked: boolean): void => {
        if (name === 'price_drops') {
            setPriceDrops(checked);
        } else if (name === 'new_items') {
            setNewItems(checked);
        } else if (name === 'matches_intent') {
            setMatchesIntent(checked);
        } else {
            setLikelyWorking(checked);
        }
    };

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(
            links.index,
            {
                // Hidden-input parity: matches_intent is submitted explicitly
                // (0 when unchecked) so the server can tell "off" from absent.
                ...(filters.huntedDeal !== null ? { hunted_deal: filters.huntedDeal } : {}),
                ...(search.trim() !== '' ? { search: search } : {}),
                ...(sort !== 'last_seen_at' ? { sort } : {}),
                ...(direction !== 'desc' ? { direction } : {}),
                ...(priceDrops ? { price_drops: 1 } : {}),
                ...(newItems ? { new_items: 1 } : {}),
                matches_intent: matchesIntent ? 1 : 0,
                ...(likelyWorking ? { likely_working: 1 } : {}),
            },
            { preserveState: false, preserveScroll: true },
        );
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Registru anunțuri</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    Anunțuri găsite
                </h2>
                <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                    {deals.meta.total} {deals.meta.total === 1 ? 'anunț' : 'anunțuri'} în registru
                </p>
            </div>
            <Link
                href={links.huntedDealsIndex}
                className="beamkey focus-ring shrink-0 rounded-sm px-4 py-2.5 text-[0.65rem]"
            >
                Căutările mele
            </Link>
        </div>
    );

    const checkboxRow = (
        name: 'price_drops' | 'new_items' | 'matches_intent' | 'likely_working',
        label: string,
        checked: boolean,
        count: number,
        accent: string,
        countAccent: string,
    ): ReactElement => (
        <label className="flex cursor-pointer items-center justify-between gap-3 border-b border-hairline pb-2.5 font-mono text-[0.7rem] tabular-nums text-dim">
            <span className="flex items-center gap-2">
                <input
                    type="checkbox"
                    name={name}
                    value="1"
                    checked={checked}
                    onChange={(event: ChangeEvent<HTMLInputElement>) => handleCheckboxChange(name, event.target.checked)}
                    className={`rounded-none border-hairline bg-[#06080a] ${accent}`}
                />{' '}
                {label}
            </span>
            <span className={countAccent}>{count}</span>
        </label>
    );

    return (
        <AppLayout title="Anunțuri găsite" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section aria-label="Filtre anunțuri">
                        <form onSubmit={handleSubmit} className="border border-hairline bg-bench px-5 py-5">
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_12rem_9rem]">
                                <div>
                                    <label htmlFor="search" className="placard text-[0.6rem]">
                                        Caută în anunțuri
                                    </label>
                                    <input
                                        id="search"
                                        name="search"
                                        type="search"
                                        value={search}
                                        onChange={(event) => setSearch(event.target.value)}
                                        placeholder="Titlu sau descriere..."
                                        className={FIELD_CLASS}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="sort" className="placard text-[0.6rem]">
                                        Ordine registru
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
                                        Sens
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

                            <div className="mt-5 border-t border-hairline pt-4">
                                <p className="placard mb-3 text-[0.6rem]">Restrânge citirea</p>
                                <div className="grid grid-cols-1 gap-x-5 gap-y-2.5 sm:grid-cols-2 xl:grid-cols-4">
                                    {checkboxRow('price_drops', 'Preț redus', priceDrops, filterCounts.priceDrops, 'text-[#ff5d5d] focus:ring-[#ff5d5d]/40', 'text-em-red')}
                                    {checkboxRow('new_items', 'Noi, 24 h', newItems, filterCounts.newItems, 'text-[#ffc46b] focus:ring-[#ffc46b]/40', 'text-em-amber')}
                                    {checkboxRow('matches_intent', 'Doar potriviri', matchesIntent, filterCounts.matchesIntent, 'text-[#7dffa8] focus:ring-[#7dffa8]/40', 'text-em-green')}
                                    {checkboxRow('likely_working', 'Funcționale', likelyWorking, filterCounts.likelyWorking, 'text-[#7dffa8] focus:ring-[#7dffa8]/40', 'text-em-green')}
                                </div>
                            </div>

                            <div className="mt-5 flex flex-wrap items-center justify-end gap-2.5">
                                <Link
                                    href={links.reset}
                                    className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                                >
                                    Resetează
                                </Link>
                                <button type="submit" className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                                    Aplică
                                </button>
                            </div>
                        </form>
                    </section>

                    <section aria-labelledby="deals-ledger-heading">
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <p className="placard text-[0.6rem]">Semnal primit</p>
                                <h3 id="deals-ledger-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">
                                    Lista anunțurilor
                                </h3>
                            </div>
                            {filters.huntedDeal !== null && (
                                <span className="font-mono text-[0.65rem] tabular-nums text-dim/70">
                                    filtrat după căutare
                                </span>
                            )}
                        </div>

                        {deals.data.length > 0 ? (
                            <>
                                <div className="border-t border-hairline">
                                    {deals.data.map((deal) => (
                                        <DealListRow
                                            key={deal.id}
                                            deal={deal}
                                            variant="ledger"
                                            titleLimit={100}
                                            description={deal.description}
                                            showNew={deal.isNew ?? false}
                                            leading={<DealMediaGallery deal={deal} mode="thumbnail" />}
                                            leadingActions={
                                                <FavoriteButton
                                                    toggleUrl={deal.toggleFavoriteUrl}
                                                    initialFavorited={deal.isFavorite}
                                                />
                                            }
                                        />
                                    ))}
                                </div>

                                {deals.meta.lastPage > 1 && (
                                    <div className="mt-6 border-t border-hairline pt-4 font-mono text-sm text-dim">
                                        <Pagination meta={deals.meta} links={deals.links} entityLabel="Anunțuri" />
                                    </div>
                                )}
                            </>
                        ) : filters.hasActiveFilters ? (
                            <EmptyState
                                title="Niciun anunț nu corespunde"
                                description="Schimbă termenul sau filtrele și încearcă din nou."
                                action={
                                    <Link
                                        href={links.reset}
                                        className="beamkey focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        Resetează filtrele
                                    </Link>
                                }
                            />
                        ) : (
                            <EmptyState
                                title="Niciun anunț încă"
                                description="Adaugă o căutare urmărită. Anunțurile găsite apar aici."
                                action={
                                    <Link
                                        href={links.huntedDealsCreate}
                                        className="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        + Căutare nouă
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
