import { useState, type FormEvent, type ReactElement } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import Pagination from '../../components/Pagination';
import StatusBadge from './StatusBadge';
import { formatNumber } from '../../lib/format';
import type { CrawlLog, CrawlLogsFilters, Paginated, SharedPageProps } from '../../types';

interface AdminCrawlLogsLinks {
    index: string;
    dashboard: string;
}

interface AdminCrawlLogsPageProps extends SharedPageProps {
    logs: Paginated<CrawlLog>;
    filters: CrawlLogsFilters;
    links: AdminCrawlLogsLinks;
}

const STATUS_OPTIONS = [
    { value: '', label: 'Toate stările' },
    { value: 'started', label: 'Pornită' },
    { value: 'completed', label: 'Finalizată' },
    { value: 'failed', label: 'Eșuată' },
    { value: 'partial', label: 'Parțială' },
] as const;

const TYPE_OPTIONS = [
    { value: '', label: 'Toate tipurile' },
    { value: 'crawl', label: 'Rulare programată' },
    { value: 'manual_crawl', label: 'Rulare manuală' },
    { value: 'health_check', label: 'Verificare sistem' },
] as const;

const FIELD_CLASS =
    'mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

export default function AdminCrawlLogs(): ReactElement {
    const { logs, filters, links } = usePage<AdminCrawlLogsPageProps>().props;

    const [status, setStatus] = useState(filters.status ?? '');
    const [type, setType] = useState(filters.type ?? '');
    const [dateFrom, setDateFrom] = useState(filters.dateFrom ?? '');
    const [dateTo, setDateTo] = useState(filters.dateTo ?? '');

    const handleSubmit = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();

        router.get(
            links.index,
            {
                ...(status !== '' ? { status } : {}),
                ...(type !== '' ? { type } : {}),
                ...(dateFrom !== '' ? { date_from: dateFrom } : {}),
                ...(dateTo !== '' ? { date_to: dateTo } : {}),
            },
            { preserveState: true, preserveScroll: true },
        );
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Administrare</p>
                <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Jurnal rulări</h2>
            </div>
            <Link href={links.dashboard} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                &larr; Panou administrare
            </Link>
        </div>
    );

    return (
        <AppLayout title="Jurnal rulări" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline bg-bench" aria-label="Filtre jurnal">
                        <form onSubmit={handleSubmit} className="p-5">
                            <div className="grid gap-4 md:grid-cols-5">
                                <div>
                                    <label htmlFor="status" className="placard text-[0.6rem]">
                                        Stare
                                    </label>
                                    <select
                                        name="status"
                                        id="status"
                                        value={status}
                                        onChange={(event) => setStatus(event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        {STATUS_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="type" className="placard text-[0.6rem]">
                                        Tip
                                    </label>
                                    <select
                                        name="type"
                                        id="type"
                                        value={type}
                                        onChange={(event) => setType(event.target.value)}
                                        className={FIELD_CLASS}
                                    >
                                        {TYPE_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="date_from" className="placard text-[0.6rem]">
                                        De la
                                    </label>
                                    <input
                                        type="date"
                                        name="date_from"
                                        id="date_from"
                                        value={dateFrom}
                                        onChange={(event) => setDateFrom(event.target.value)}
                                        className={FIELD_CLASS}
                                    />
                                </div>
                                <div>
                                    <label htmlFor="date_to" className="placard text-[0.6rem]">
                                        Până la
                                    </label>
                                    <input
                                        type="date"
                                        name="date_to"
                                        id="date_to"
                                        value={dateTo}
                                        onChange={(event) => setDateTo(event.target.value)}
                                        className={FIELD_CLASS}
                                    />
                                </div>
                                <div className="flex items-end">
                                    <button
                                        type="submit"
                                        className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                                    >
                                        Aplică filtrele
                                    </button>
                                </div>
                            </div>
                        </form>
                    </section>

                    <section aria-labelledby="ledger-heading">
                        <div className="mb-4">
                            <p className="placard text-[0.6rem]">Trasă cronologică</p>
                            <h3 id="ledger-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                Execuții înregistrate
                            </h3>
                        </div>

                        <div className="overflow-x-auto border-y border-hairline">
                            <table className="min-w-[65rem] w-full text-left">
                                <thead className="border-b border-hairline bg-bench">
                                    <tr className="placard text-[0.55rem]">
                                        <th className="px-4 py-3 font-medium">Pornire</th>
                                        <th className="px-4 py-3 font-medium">Tip / stare</th>
                                        <th className="px-4 py-3 font-medium">Durată</th>
                                        <th className="px-4 py-3 font-medium">Căutări</th>
                                        <th className="px-4 py-3 font-medium">Anunțuri</th>
                                        <th className="px-4 py-3 font-medium">Noi</th>
                                        <th className="px-4 py-3 font-medium">Erori</th>
                                        <th className="px-4 py-3 font-medium">Declanșată de</th>
                                        <th className="px-4 py-3 font-medium"></th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-hairline">
                                    {logs.data.map((log) => (
                                        <tr key={log.id} className="transition-colors hover:bg-bench/60">
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {log.startedAtDate}
                                                <span className="mt-1 block text-dim/70">{log.startedAtTime}</span>
                                            </td>
                                            <td className="px-4 py-4">
                                                <p className="font-sans text-sm text-[#eaf4f6]">{log.typeLabel}</p>
                                                <StatusBadge className="mt-1" status={log.status} totalErrors={log.totalErrors} />
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {log.formattedDuration}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {log.huntedDealsProcessed}
                                                {log.huntedDealsFailed > 0 && (
                                                    <span className="mt-1 block text-em-red">{log.huntedDealsFailed} eșuate</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {formatNumber(log.totalListingsFound)}
                                                {log.listingsPerSecond !== null && (
                                                    <span className="mt-1 block text-dim/70">{log.listingsPerSecond}/s</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {formatNumber(log.newDealsCreated)}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums">
                                                {log.totalErrors > 0 ? (
                                                    <span className="text-em-red">{log.totalErrors}</span>
                                                ) : (
                                                    <span className="text-dim/70">0</span>
                                                )}
                                            </td>
                                            <td className="px-4 py-4 font-mono text-[0.7rem] tabular-nums text-[#eaf4f6]">
                                                {log.triggeredByLabel}
                                                {log.userName && <span className="mt-1 block text-dim/70">{log.userName}</span>}
                                            </td>
                                            <td className="px-4 py-4 text-right">
                                                <Link
                                                    href={log.showUrl}
                                                    className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                                                >
                                                    Detalii
                                                </Link>
                                            </td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>

                        {logs.data.length === 0 && (
                            <p className="border-b border-hairline px-5 py-8 text-sm text-dim">Nu există rulări înregistrate.</p>
                        )}

                        {logs.meta.lastPage > 1 && (
                            <div className="mt-6">
                                <Pagination meta={logs.meta} links={logs.links} entityLabel="Execuții" />
                            </div>
                        )}
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
