import { type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import StatusBadge from './StatusBadge';
import { formatNumber } from '../../lib/format';
import type {
    CrawlerConfig,
    CrawlStats,
    HuntedDealOption,
    RecentCrawl,
    SharedPageProps,
    SystemComponentHealth,
    SystemHealthOverview,
} from '../../types';

interface AdminDashboardLinks {
    systemHealth: string;
    crawlLogs: string;
    configuration: string;
    runHealthCheck: string;
    triggerCrawl: string;
}

interface AdminDashboardPageProps extends SharedPageProps {
    crawlStats: CrawlStats;
    systemHealth: SystemHealthOverview;
    systemComponents: Record<string, SystemComponentHealth | null>;
    recentCrawls: RecentCrawl[];
    activeHuntedDeals: number;
    totalDeals: number;
    crawlerConfig: CrawlerConfig;
    huntedDealsOptions: HuntedDealOption[];
    links: AdminDashboardLinks;
}

const FIELD_CLASS =
    'mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

export default function AdminDashboard(): ReactElement {
    const {
        crawlStats,
        systemHealth,
        systemComponents,
        recentCrawls,
        activeHuntedDeals,
        totalDeals,
        crawlerConfig,
        huntedDealsOptions,
        links,
    } = usePage<AdminDashboardPageProps>().props;

    const manualForm = useForm({
        hunted_deal_id: '',
        dry_run: false,
        notes: '',
    });

    const healthForm = useForm({});

    const submitManualCrawl = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        manualForm.post(links.triggerCrawl, { preserveScroll: true });
    };

    const runHealthCheck = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        healthForm.post(links.runHealthCheck, { preserveScroll: true });
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Administrare</p>
                <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Panou de control</h2>
            </div>
            <div className="flex flex-wrap gap-2.5">
                <Link href={links.systemHealth} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Stare sistem
                </Link>
                <Link href={links.crawlLogs} className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Jurnal rulări
                </Link>
            </div>
        </div>
    );

    return (
        <AppLayout title="Panou de control" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline graticule" aria-labelledby="alignment-heading">
                        <div className="flex flex-col gap-5 border-b border-hairline px-5 py-4 sm:flex-row sm:items-end sm:justify-between">
                            <div>
                                <p className="placard text-[0.6rem]">Aliniere curentă</p>
                                <h3 id="alignment-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Stare sistem
                                </h3>
                            </div>
                            <div className="flex items-center gap-4">
                                <p className="font-mono text-[0.65rem] tabular-nums text-dim/70">
                                    ultima citire {systemHealth.lastCheckLabel ?? 'niciodată'}
                                </p>
                                <form onSubmit={runHealthCheck}>
                                    <button
                                        type="submit"
                                        disabled={healthForm.processing}
                                        className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem] disabled:pointer-events-none disabled:opacity-40"
                                    >
                                        Rulează verificarea
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div className="divide-y divide-hairline">
                            {Object.entries(systemComponents).length > 0 ? (
                                Object.entries(systemComponents).map(([component, health]) => (
                                    <div
                                        key={component}
                                        className="grid gap-3 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_10rem_8rem] sm:items-center"
                                    >
                                        <div>
                                            <p className="font-sans font-semibold capitalize text-[#eaf4f6]">
                                                {component.replace(/_/g, ' ')}
                                            </p>
                                            {health && <p className="mt-1 text-sm text-dim">{health.message}</p>}
                                        </div>
                                        {health ? (
                                            <StatusBadge status={health.status} />
                                        ) : (
                                            <StatusBadge status="pending" />
                                        )}
                                        <p className="font-mono text-[0.7rem] tabular-nums text-dim sm:text-right">
                                            {health?.responseTimeMs ? `${health.responseTimeMs} ms` : 'fără timp'}
                                        </p>
                                    </div>
                                ))
                            ) : (
                                <p className="px-5 py-8 text-sm text-dim">Nu există componente de afișat.</p>
                            )}
                        </div>
                    </section>

                    <section aria-label="Citiri în ultimele 24 de ore" className="border-y border-hairline">
                        <div className="grid grid-cols-2 divide-x divide-y divide-hairline lg:grid-cols-4 lg:divide-y-0">
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Rulări, 24 h</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {crawlStats.totalCrawls}
                                </p>
                                <p className="mt-1 text-sm text-em-green">{crawlStats.successfulCrawls} reușite</p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Anunțuri citite</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {formatNumber(crawlStats.totalListingsFound)}
                                </p>
                                <p className="mt-1 text-sm text-beam">{formatNumber(crawlStats.totalDealsCreated)} noi</p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Căutări active</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {activeHuntedDeals}
                                </p>
                                <p className="mt-1 text-sm text-dim">{formatNumber(totalDeals)} anunțuri total</p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Rată reușită</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {(crawlStats.averageSuccessRate ?? 0).toFixed(1)}%
                                </p>
                                <p className="mt-1 text-sm text-dim">ultimele 24 de ore</p>
                            </div>
                        </div>
                    </section>

                    <section className="border border-hairline bg-bench" aria-labelledby="manual-heading">
                        <div className="border-b border-hairline px-5 py-4">
                            <p className="placard text-[0.6rem]">Execuție</p>
                            <h3 id="manual-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                Pornește o rulare manuală
                            </h3>
                        </div>
                        <form onSubmit={submitManualCrawl} className="px-5 py-5">
                            <div className="grid gap-4 md:grid-cols-3">
                                <div>
                                    <label htmlFor="hunted_deal_id" className="placard text-[0.6rem]">
                                        Căutare urmărită
                                    </label>
                                    <select
                                        name="hunted_deal_id"
                                        id="hunted_deal_id"
                                        value={manualForm.data.hunted_deal_id}
                                        onChange={(event) => manualForm.setData('hunted_deal_id', event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        <option value="">Toate căutările active</option>
                                        {huntedDealsOptions.map((option) => (
                                            <option key={option.id} value={option.id}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="notes" className="placard text-[0.6rem]">
                                        Notițe
                                    </label>
                                    <input
                                        type="text"
                                        name="notes"
                                        id="notes"
                                        value={manualForm.data.notes}
                                        onChange={(event) => manualForm.setData('notes', event.target.value)}
                                        placeholder="Motivul rulării manuale"
                                        className={FIELD_CLASS}
                                    />
                                </div>
                                <label className="flex items-end gap-2 pb-2 font-mono text-[0.7rem] text-dim">
                                    <input
                                        type="checkbox"
                                        name="dry_run"
                                        checked={manualForm.data.dry_run}
                                        onChange={(event) => manualForm.setData('dry_run', event.target.checked)}
                                        className="rounded-sm border-hairline bg-[#06080a] text-[#59e3ff] shadow-none focus:ring-[#59e3ff]/40"
                                    />
                                    Simulare
                                </label>
                            </div>
                            <div className="mt-5 flex justify-end">
                                <button
                                    type="submit"
                                    disabled={manualForm.processing}
                                    className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem] disabled:pointer-events-none disabled:opacity-40"
                                >
                                    Pornește rularea
                                </button>
                            </div>
                        </form>
                    </section>

                    <section aria-labelledby="recent-heading">
                        <div className="mb-4 flex items-end justify-between gap-3">
                            <div>
                                <p className="placard text-[0.6rem]">Trasă de execuție</p>
                                <h3 id="recent-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Rulări recente
                                </h3>
                            </div>
                            <Link
                                href={links.crawlLogs}
                                className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                            >
                                Jurnal complet &rarr;
                            </Link>
                        </div>

                        <div className="border-t border-hairline">
                            {recentCrawls.length > 0 ? (
                                recentCrawls.map((crawl) => (
                                    <article
                                        key={crawl.id}
                                        className="grid gap-3 border-b border-hairline py-4 sm:grid-cols-[10rem_minmax(0,1fr)_9rem] sm:items-center"
                                    >
                                        <p className="font-mono text-[0.7rem] tabular-nums text-dim">{crawl.startedAtLabel}</p>
                                        <div>
                                            <div className="flex flex-wrap items-center gap-x-3 gap-y-1">
                                                <p className="font-sans font-semibold text-[#eaf4f6]">{crawl.typeLabel}</p>
                                                <StatusBadge status={crawl.status} totalErrors={crawl.totalErrors} />
                                            </div>
                                            <p className="mt-1 font-mono text-[0.65rem] tabular-nums text-dim/70">
                                                {crawl.totalListingsFound} găsite · {crawl.newDealsCreated} noi ·{' '}
                                                {crawl.triggeredByLabel}
                                                {crawl.userName ? ` (${crawl.userName})` : ''}
                                            </p>
                                        </div>
                                        <Link
                                            href={crawl.showUrl}
                                            className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem] sm:justify-self-end"
                                        >
                                            Detalii
                                        </Link>
                                    </article>
                                ))
                            ) : (
                                <p className="border-b border-hairline px-5 py-8 text-sm text-dim">Nu există rulări recente.</p>
                            )}
                        </div>
                    </section>

                    <section className="border border-hairline bg-bench" aria-labelledby="config-heading">
                        <div className="flex items-end justify-between gap-3 border-b border-hairline px-5 py-4">
                            <div>
                                <p className="placard text-[0.6rem]">Citire configurație</p>
                                <h3 id="config-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Parametri activi
                                </h3>
                            </div>
                            <Link
                                href={links.configuration}
                                className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                            >
                                Vezi configurația &rarr;
                            </Link>
                        </div>
                        <dl className="grid divide-x divide-y divide-hairline sm:grid-cols-2">
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Pagină maximă / căutare</dt>
                                <dd className="mt-2 font-mono tabular-nums text-[#eaf4f6]">{crawlerConfig.maxPagesPerSearch}</dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Întârziere cerere</dt>
                                <dd className="mt-2 font-mono tabular-nums text-[#eaf4f6]">{crawlerConfig.requestDelayMs} ms</dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Anunțuri maxime / rulare</dt>
                                <dd className="mt-2 font-mono tabular-nums text-[#eaf4f6]">{crawlerConfig.maxListingsPerRun}</dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Clasificare AI</dt>
                                <dd
                                    className={`mt-2 font-mono text-[0.75rem] ${
                                        crawlerConfig.aiClassificationEnabled ? 'text-em-green' : 'text-em-red'
                                    }`}
                                >
                                    {crawlerConfig.aiClassificationEnabled ? 'Activată' : 'Dezactivată'}
                                </dd>
                            </div>
                        </dl>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
