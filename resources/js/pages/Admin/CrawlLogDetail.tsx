import { type ReactElement } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import StatusBadge from './StatusBadge';
import { formatNumber } from '../../lib/format';
import type { CrawlLogDetail, SharedPageProps } from '../../types';

interface AdminCrawlLogDetailLinks {
    crawlLogs: string;
}

interface AdminCrawlLogDetailPageProps extends SharedPageProps {
    crawlLog: CrawlLogDetail;
    links: AdminCrawlLogDetailLinks;
}

export default function AdminCrawlLogDetail(): ReactElement {
    const { crawlLog, links } = usePage<AdminCrawlLogDetailPageProps>().props;

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Administrare / Jurnal rulări</p>
                <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Detalii rulare</h2>
            </div>
            <Link href={links.crawlLogs} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                &larr; Jurnal rulări
            </Link>
        </div>
    );

    return (
        <AppLayout title="Detalii rulare" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline graticule" aria-labelledby="trace-heading">
                        <div className="flex flex-wrap items-end justify-between gap-4 border-b border-hairline px-5 py-4">
                            <div>
                                <p className="placard text-[0.6rem]">Trasă de execuție</p>
                                <h3 id="trace-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Rulare #{crawlLog.id}
                                </h3>
                            </div>
                            <StatusBadge status={crawlLog.status} totalErrors={crawlLog.totalErrors} />
                        </div>
                        <dl className="grid divide-x divide-y divide-hairline md:grid-cols-2">
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Tip</dt>
                                <dd className="mt-2 font-sans text-[#eaf4f6]">{crawlLog.typeLabel}</dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Declanșată de</dt>
                                <dd className="mt-2 text-[#eaf4f6]">
                                    {crawlLog.triggeredByLabel}
                                    {crawlLog.userName ? ` (${crawlLog.userName})` : ''}
                                </dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Pornită</dt>
                                <dd className="mt-2 font-mono text-[0.75rem] tabular-nums text-[#eaf4f6]">
                                    {crawlLog.startedAtLabel}
                                </dd>
                            </div>
                            <div className="p-5">
                                <dt className="placard text-[0.55rem]">Finalizată / durată</dt>
                                <dd className="mt-2 font-mono text-[0.75rem] tabular-nums text-[#eaf4f6]">
                                    {crawlLog.completedAtLabel ?? 'nefinalizată'} · {crawlLog.formattedDuration}
                                </dd>
                            </div>
                            {crawlLog.notes && (
                                <div className="p-5 md:col-span-2">
                                    <dt className="placard text-[0.55rem]">Notițe</dt>
                                    <dd className="mt-2 text-sm text-dim">{crawlLog.notes}</dd>
                                </div>
                            )}
                        </dl>
                    </section>

                    <section aria-label="Statistici rulare" className="border-y border-hairline">
                        <div className="grid grid-cols-2 divide-x divide-y divide-hairline lg:grid-cols-4 lg:divide-y-0">
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Căutări procesate</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {crawlLog.huntedDealsProcessed}
                                </p>
                                {crawlLog.huntedDealsFailed > 0 && (
                                    <p className="mt-1 font-mono text-[0.65rem] text-em-red">{crawlLog.huntedDealsFailed} eșuate</p>
                                )}
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Anunțuri găsite</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {formatNumber(crawlLog.totalListingsFound)}
                                </p>
                                {crawlLog.listingsPerSecond !== null && (
                                    <p className="mt-1 font-mono text-[0.65rem] text-dim">{crawlLog.listingsPerSecond}/s</p>
                                )}
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Anunțuri noi</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {formatNumber(crawlLog.newDealsCreated)}
                                </p>
                                {crawlLog.dealsUpdated > 0 && (
                                    <p className="mt-1 font-mono text-[0.65rem] text-dim">
                                        {formatNumber(crawlLog.dealsUpdated)} actualizate
                                    </p>
                                )}
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Instantanee create</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-[#eaf4f6]">
                                    {formatNumber(crawlLog.snapshotsCreated)}
                                </p>
                                {crawlLog.successRate !== null && (
                                    <p className="mt-1 font-mono text-[0.65rem] text-em-green">{crawlLog.successRate}% reușită</p>
                                )}
                            </div>
                        </div>
                    </section>

                    {crawlLog.configuration.length > 0 && (
                        <section className="border border-hairline bg-bench" aria-labelledby="config-heading">
                            <div className="border-b border-hairline px-5 py-4">
                                <p className="placard text-[0.6rem]">Sursă execuție</p>
                                <h3 id="config-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Configurația rulării
                                </h3>
                            </div>
                            <dl className="divide-y divide-hairline">
                                {crawlLog.configuration.map((entry) => (
                                    <div
                                        key={entry.key}
                                        className="grid gap-2 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_minmax(14rem,1fr)]"
                                    >
                                        <dt className="font-sans text-sm font-semibold text-[#eaf4f6]">
                                            {entry.key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())}
                                        </dt>
                                        <dd className="break-all font-mono text-[0.7rem] text-dim">{entry.value}</dd>
                                    </div>
                                ))}
                            </dl>
                        </section>
                    )}

                    {crawlLog.totalErrors > 0 && crawlLog.errors.length > 0 && (
                        <section className="border border-[#ff5d5d]/50" aria-labelledby="errors-heading">
                            <div className="border-b border-[#ff5d5d]/50 px-5 py-4">
                                <p className="placard text-[0.6rem] text-em-red">Erori factuale</p>
                                <h3 id="errors-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Erori ({crawlLog.totalErrors})
                                </h3>
                            </div>
                            <ol className="divide-y divide-hairline">
                                {crawlLog.errors.map((error, index) => (
                                    <li key={`${index}-${error}`} className="grid gap-3 px-5 py-4 sm:grid-cols-[4rem_minmax(0,1fr)]">
                                        <span className="font-mono text-[0.65rem] text-em-red">#{index + 1}</span>
                                        <p className="break-words text-sm text-dim">{error}</p>
                                    </li>
                                ))}
                            </ol>
                        </section>
                    )}

                    {crawlLog.listingsPerSecond !== null && (
                        <section className="border border-hairline" aria-labelledby="performance-heading">
                            <div className="border-b border-hairline bg-bench px-5 py-4">
                                <p className="placard text-[0.6rem]">Măsurători</p>
                                <h3 id="performance-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Performanța execuției
                                </h3>
                            </div>
                            <dl className="grid divide-x divide-y divide-hairline sm:grid-cols-3 sm:divide-y-0">
                                <div className="p-5">
                                    <dt className="placard text-[0.55rem]">Durată totală</dt>
                                    <dd className="mt-2 font-mono text-lg tabular-nums text-[#eaf4f6]">
                                        {crawlLog.formattedDuration}
                                    </dd>
                                </div>
                                <div className="p-5">
                                    <dt className="placard text-[0.55rem]">Anunțuri pe secundă</dt>
                                    <dd className="mt-2 font-mono text-lg tabular-nums text-[#eaf4f6]">
                                        {crawlLog.listingsPerSecond}
                                    </dd>
                                </div>
                                {crawlLog.averageMsPerSearch !== null && (
                                    <div className="p-5">
                                        <dt className="placard text-[0.55rem]">Timp mediu / căutare</dt>
                                        <dd className="mt-2 font-mono text-lg tabular-nums text-[#eaf4f6]">
                                            {crawlLog.averageMsPerSearch} ms
                                        </dd>
                                    </div>
                                )}
                            </dl>
                        </section>
                    )}
                </div>
            </div>
        </AppLayout>
    );
}
