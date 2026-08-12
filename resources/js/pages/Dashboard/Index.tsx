import { useEffect, useRef, useState, type ReactElement } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import StatCard from '../../components/StatCard';
import DealListRow from '../../components/deals/DealListRow';
import DealMediaGallery from '../../components/deals/DealMediaGallery';
import FavoriteButton from '../../components/deals/FavoriteButton';
import { EmptyState } from '../../components/ui/EmptyState';
import type { Deal, HuntedDeal, SharedPageProps } from '../../types';

/** Auto-refresh interval mirrored from the Blade dashboard (5 minutes). */
const AUTO_REFRESH_INTERVAL_MS = 300_000;

interface DashboardLinks {
    huntedDealsIndex: string;
    huntedDealsActive: string;
    huntedDealsCreate: string;
    dealsIndex: string;
    newDealsIndex: string;
}

interface DashboardPageProps extends SharedPageProps {
    huntedDealsCount: number;
    activeHuntedDealsCount: number;
    totalDealsCount: number;
    newDealsCount: number;
    huntedDeals: HuntedDeal[];
    recentDeals: Deal[];
    links: DashboardLinks;
}

function HuntedDealRow({ huntedDeal }: { huntedDeal: HuntedDeal }): ReactElement {
    return (
        <div className="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div className="min-w-0 flex-1">
                    <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                        <Link
                            href={huntedDeal.showUrl}
                            className="focus-ring break-words rounded-sm font-sans font-semibold text-[#eaf4f6] transition-colors group-hover:text-beam"
                        >
                            {huntedDeal.searchTerm}
                        </Link>
                        {huntedDeal.isActive ? (
                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                                <span
                                    className="inline-block h-1 w-1 rounded-full bg-[#7dffa8]"
                                    style={{ boxShadow: '0 0 6px rgba(125,255,168,0.6)' }}
                                    aria-hidden="true"
                                ></span>
                                Activă
                            </span>
                        ) : (
                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-dim/70">
                                <span className="inline-block h-1 w-1 rounded-full bg-[#1c242a]" aria-hidden="true"></span>
                                În pauză
                            </span>
                        )}
                    </div>
                    {huntedDeal.notes && (
                        <p className="mt-1 max-w-[68ch] break-words text-sm text-dim">{huntedDeal.notes}</p>
                    )}
                    <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                        {huntedDeal.dealsCount}{' '}
                        {huntedDeal.dealsCount === 1 ? 'anunț găsit' : 'anunțuri găsite'}
                        &middot;{' '}
                        {huntedDeal.lastCrawledAt
                            ? `ultima verificare ${huntedDeal.lastCrawledAt}`
                            : 'neverificată încă'}
                    </p>
                </div>
                <div className="flex shrink-0 items-center gap-4 sm:gap-5">
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
    );
}

export default function DashboardIndex(): ReactElement {
    const {
        auth,
        huntedDealsCount,
        activeHuntedDealsCount,
        totalDealsCount,
        newDealsCount,
        huntedDeals,
        recentDeals,
        links,
    } = usePage<DashboardPageProps>().props;

    const [autoRefresh, setAutoRefresh] = useState(false);
    const [refreshing, setRefreshing] = useState(false);
    const intervalRef = useRef<ReturnType<typeof setInterval> | null>(null);

    useEffect(() => {
        if (autoRefresh) {
            intervalRef.current = setInterval(() => router.reload(), AUTO_REFRESH_INTERVAL_MS);
        }

        return () => {
            if (intervalRef.current !== null) {
                clearInterval(intervalRef.current);
                intervalRef.current = null;
            }
        };
    }, [autoRefresh]);

    const handleManualRefresh = (): void => {
        if (refreshing) {
            return;
        }

        setRefreshing(true);
        router.reload({
            onFinish: () => setRefreshing(false),
        });
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Panou &middot; {auth.user?.name}</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">
                    Anunțurile tale, dintr-o privire
                </h2>
            </div>
            <div className="flex flex-wrap items-center gap-2.5">
                <button
                    type="button"
                    onClick={() => setAutoRefresh((previous) => !previous)}
                    className={`beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]${autoRefresh ? ' beamkey-armed' : ''}`}
                    aria-pressed={autoRefresh}
                >
                    <span
                        className={`inline-block h-1.5 w-1.5 rounded-full ${autoRefresh ? 'bg-[#7dffa8]' : 'bg-[#1c242a]'}`}
                        style={autoRefresh ? { boxShadow: '0 0 8px rgba(125,255,168,0.6)' } : undefined}
                        aria-hidden="true"
                    ></span>
                    <span>{autoRefresh ? 'Auto-refresh pornit' : 'Auto-refresh oprit'}</span>
                </button>
                <button
                    type="button"
                    onClick={handleManualRefresh}
                    className={`beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]${refreshing ? ' pointer-events-none opacity-50' : ''}`}
                    disabled={refreshing}
                >
                    <svg
                        className={`h-3.5 w-3.5${refreshing ? ' animate-spin' : ''}`}
                        fill="none"
                        stroke="currentColor"
                        viewBox="0 0 24 24"
                        aria-hidden="true"
                    >
                        <path
                            strokeLinecap="round"
                            strokeLinejoin="round"
                            strokeWidth="1.5"
                            d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"
                        ></path>
                    </svg>
                    <span>{refreshing ? 'Se reîncarcă…' : 'Reîncarcă'}</span>
                </button>
                <Link
                    href={links.huntedDealsCreate}
                    className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                >
                    + Căutare nouă
                </Link>
            </div>
        </div>
    );

    return (
        <AppLayout title="Panou" header={header}>
            <div className="space-y-8 py-8 sm:space-y-10 sm:py-10">
                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* ============ SPECTRAL READOUTS ============ */}
                    <section aria-label="Statistici">
                        <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-4">
                            <StatCard
                                title="Căutări urmărite"
                                value={huntedDealsCount}
                                accent="cyan"
                                href={links.huntedDealsIndex}
                            />
                            <StatCard
                                title="Căutări active"
                                value={activeHuntedDealsCount}
                                accent="green"
                                href={links.huntedDealsActive}
                            />
                            <StatCard
                                title="Anunțuri găsite"
                                value={totalDealsCount}
                                accent="cyan"
                                href={links.dealsIndex}
                            />
                            <StatCard
                                title="Noi în 24 de ore"
                                value={newDealsCount}
                                accent={newDealsCount > 0 ? 'amber' : 'dim'}
                                href={links.newDealsIndex}
                            />
                        </div>
                    </section>
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* ============ HUNTED SEARCHES LEDGER ============ */}
                    <section aria-labelledby="hunted-heading">
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 id="hunted-heading" className="font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">
                                    Căutările tale urmărite
                                </h3>
                                <p className="mt-1 max-w-[56ch] text-sm text-dim">
                                    Fiecare căutare salvată este verificată automat pe OLX România.
                                </p>
                            </div>
                            <Link
                                href={links.huntedDealsIndex}
                                className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                            >
                                Toate căutările &rarr;
                            </Link>
                        </div>

                        {huntedDeals.length > 0 ? (
                            <div className="border-t border-hairline">
                                {huntedDeals.map((huntedDeal) => (
                                    <HuntedDealRow key={huntedDeal.id} huntedDeal={huntedDeal} />
                                ))}
                            </div>
                        ) : (
                            <EmptyState
                                title="Nicio căutare urmărită încă"
                                description={
                                    <>
                                        Salvează prima căutare — de exemplu „iPhone 13&rdquo; — și o verificăm automat
                                        pe OLX România. Anunțurile noi și schimbările de preț apar aici.
                                    </>
                                }
                                action={
                                    <Link
                                        href={links.huntedDealsCreate}
                                        className="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]"
                                    >
                                        + Adaugă prima căutare
                                    </Link>
                                }
                            />
                        )}
                    </section>
                </div>

                <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                    {/* ============ RECENT LISTINGS LEDGER ============ */}
                    <section aria-labelledby="recent-heading">
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 id="recent-heading" className="font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">
                                    Anunțuri recente
                                </h3>
                                <p className="mt-1 max-w-[56ch] text-sm text-dim">
                                    Ultimele anunțuri găsite de căutările tale.
                                </p>
                            </div>
                            <Link
                                href={links.dealsIndex}
                                className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                            >
                                Toate anunțurile &rarr;
                            </Link>
                        </div>

                        {recentDeals.length > 0 ? (
                            <div className="border-t border-hairline">
                                {recentDeals.map((deal) => (
                                    <DealListRow
                                        key={deal.id}
                                        deal={deal}
                                        variant="dashboard"
                                        titleLimit={60}
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
                        ) : (
                            <div className="border border-hairline px-6 py-10 text-center">
                                <p className="font-sans font-semibold text-[#eaf4f6]">Niciun anunț găsit încă</p>
                                <p className="mx-auto mt-1.5 max-w-[52ch] text-sm text-dim">
                                    Adaugă căutări urmărite și așteaptă ca verificarea automată să adune anunțuri —
                                    ele apar aici.
                                </p>
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
