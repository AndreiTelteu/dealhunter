import { useState, type FormEvent, type ReactElement } from 'react';
import { Link, useForm, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import StatusBadge from './StatusBadge';
import { ConfirmDialog } from '../../components/ui/ConfirmDialog';
import { DangerButton } from '../../components/ui/DangerButton';
import type { HealthHistoryComponent, SharedPageProps, SystemHealthCheck, SystemHealthOverview } from '../../types';

interface AdminSystemHealthLinks {
    dashboard: string;
    runHealthCheck: string;
    cleanupLogs: string;
}

interface AdminSystemHealthPageProps extends SharedPageProps {
    overallHealth: SystemHealthOverview;
    healthResults: SystemHealthCheck[];
    healthHistory: HealthHistoryComponent[];
    links: AdminSystemHealthLinks;
}

interface CleanupFormData {
    crawl_logs_days: number;
    health_logs_days: number;
}

const FIELD_CLASS =
    'mt-2 block w-full rounded-sm border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

export default function AdminSystemHealth(): ReactElement {
    const { overallHealth, healthResults, healthHistory, links } =
        usePage<AdminSystemHealthPageProps>().props;

    const healthForm = useForm({});
    const cleanupForm = useForm<CleanupFormData>({
        crawl_logs_days: 30,
        health_logs_days: 7,
    });
    const [confirmingCleanup, setConfirmingCleanup] = useState(false);

    const runHealthCheck = (event: FormEvent<HTMLFormElement>): void => {
        event.preventDefault();
        healthForm.post(links.runHealthCheck, { preserveScroll: true });
    };

    const handleCleanup = (): void => {
        cleanupForm.post(links.cleanupLogs, {
            preserveScroll: true,
            onSuccess: () => setConfirmingCleanup(false),
        });
    };

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Administrare</p>
                <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Stare sistem</h2>
            </div>
            <div className="flex flex-wrap gap-2.5">
                <form onSubmit={runHealthCheck}>
                    <button
                        type="submit"
                        disabled={healthForm.processing}
                        className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem] disabled:pointer-events-none disabled:opacity-40"
                    >
                        Rulează verificarea
                    </button>
                </form>
                <Link href={links.dashboard} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Panou administrare
                </Link>
            </div>
        </div>
    );

    return (
        <AppLayout title="Stare sistem" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline graticule" aria-labelledby="overall-heading">
                        <div className="flex flex-wrap items-end justify-between gap-4 border-b border-hairline px-5 py-4">
                            <div>
                                <p className="placard text-[0.6rem]">Aliniere generală</p>
                                <h3 id="overall-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Citire sistem
                                </h3>
                            </div>
                            <StatusBadge status={overallHealth.overallStatus} className="text-[0.7rem]" />
                        </div>
                        <div className="grid grid-cols-2 divide-x divide-y divide-hairline lg:grid-cols-4 lg:divide-y-0">
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">În regulă</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-em-green">
                                    {overallHealth.summary.healthy}
                                </p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Atenție</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-em-amber">
                                    {overallHealth.summary.warning}
                                </p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Critice</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-em-red">
                                    {overallHealth.summary.critical}
                                </p>
                            </div>
                            <div className="p-5">
                                <p className="placard text-[0.55rem]">Necunoscute</p>
                                <p className="mt-2 font-mono text-2xl font-bold tabular-nums text-dim">
                                    {overallHealth.summary.unknown}
                                </p>
                            </div>
                        </div>
                        <p className="border-t border-hairline px-5 py-3 font-mono text-[0.65rem] tabular-nums text-dim/70">
                            ultima verificare {overallHealth.lastCheckLabel ?? 'niciodată'}
                        </p>
                    </section>

                    <section aria-labelledby="components-heading">
                        <div className="mb-4">
                            <p className="placard text-[0.6rem]">Componente</p>
                            <h3 id="components-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                Citiri individuale
                            </h3>
                        </div>
                        <div className="border-t border-hairline">
                            {healthResults.map((health) => (
                                <article
                                    key={health.name}
                                    className="grid gap-4 border-b border-hairline py-5 lg:grid-cols-[12rem_minmax(0,1fr)_10rem] lg:items-start"
                                >
                                    <div>
                                        <p className="font-sans font-semibold capitalize text-[#eaf4f6]">
                                            {health.name.replace(/_/g, ' ')}
                                        </p>
                                        <StatusBadge className="mt-2" status={health.status} />
                                    </div>
                                    <div>
                                        <p className="text-sm text-dim">{health.message}</p>
                                        {health.details.length > 0 && (
                                            <dl className="mt-3 divide-y divide-hairline border-t border-hairline font-mono text-[0.65rem] tabular-nums">
                                                {health.details.map((detail) => (
                                                    <div key={detail.key} className="flex justify-between gap-4 py-2">
                                                        <dt className="text-dim">
                                                            {detail.key.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase())}
                                                        </dt>
                                                        <dd className="break-all text-right text-[#eaf4f6]">{detail.value}</dd>
                                                    </div>
                                                ))}
                                            </dl>
                                        )}
                                    </div>
                                    <dl className="font-mono text-[0.7rem] tabular-nums text-dim lg:text-right">
                                        <div>
                                            <dt className="placard text-[0.55rem]">Timp răspuns</dt>
                                            <dd className="mt-1 text-[#eaf4f6]">
                                                {health.responseTimeMs !== null ? `${health.responseTimeMs} ms` : 'fără timp'}
                                            </dd>
                                        </div>
                                        <div className="mt-3">
                                            <dt className="placard text-[0.55rem]">Verificată</dt>
                                            <dd className="mt-1">{health.checkedAtLabel}</dd>
                                        </div>
                                    </dl>
                                </article>
                            ))}
                        </div>
                    </section>

                    {healthHistory.length > 0 && (
                        <section className="border border-hairline graticule" aria-labelledby="history-heading">
                            <div className="border-b border-hairline px-5 py-4">
                                <p className="placard text-[0.6rem]">Istoric, 24 h</p>
                                <h3 id="history-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    Timp de răspuns
                                </h3>
                            </div>
                            <div className="grid divide-y divide-hairline md:grid-cols-2 md:divide-x md:divide-y-0">
                                {healthHistory.map((history) => (
                                    <div key={history.component} className="p-5">
                                        <p className="font-sans font-semibold capitalize text-[#eaf4f6]">{history.label}</p>
                                        <div className="mt-5 flex h-28 items-end gap-1 border-b border-hairline">
                                            {history.bars.map((bar, index) => (
                                                <span
                                                    key={`${history.component}-${index}`}
                                                    className="w-2"
                                                    style={{ height: `${bar.height}%`, backgroundColor: bar.color }}
                                                    title={bar.title}
                                                />
                                            ))}
                                        </div>
                                        <p className="mt-2 font-mono text-[0.65rem] tabular-nums text-dim">
                                            ultima: {history.lastValueMs !== null ? `${history.lastValueMs} ms` : 'fără valoare'}
                                        </p>
                                    </div>
                                ))}
                            </div>
                        </section>
                    )}

                    <section className="border border-hairline bg-bench" aria-labelledby="cleanup-heading">
                        <div className="border-b border-hairline px-5 py-4">
                            <p className="placard text-[0.6rem]">Întreținere</p>
                            <h3 id="cleanup-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                Șterge jurnalul vechi
                            </h3>
                        </div>
                        <div className="p-5">
                            <div className="grid gap-4 md:grid-cols-2">
                                <div>
                                    <label htmlFor="crawl_logs_days" className="placard text-[0.6rem]">
                                        Păstrează rulări, zile
                                    </label>
                                    <input
                                        type="number"
                                        name="crawl_logs_days"
                                        id="crawl_logs_days"
                                        min={1}
                                        max={365}
                                        value={cleanupForm.data.crawl_logs_days}
                                        onChange={(event) =>
                                            cleanupForm.setData('crawl_logs_days', Number(event.target.value))
                                        }
                                        className={FIELD_CLASS}
                                    />
                                    <p className="mt-1 text-xs text-dim">Rulările mai vechi vor fi șterse.</p>
                                </div>
                                <div>
                                    <label htmlFor="health_logs_days" className="placard text-[0.6rem]">
                                        Păstrează verificări sistem, zile
                                    </label>
                                    <input
                                        type="number"
                                        name="health_logs_days"
                                        id="health_logs_days"
                                        min={1}
                                        max={90}
                                        value={cleanupForm.data.health_logs_days}
                                        onChange={(event) =>
                                            cleanupForm.setData('health_logs_days', Number(event.target.value))
                                        }
                                        className={FIELD_CLASS}
                                    />
                                    <p className="mt-1 text-xs text-dim">Verificările mai vechi vor fi șterse.</p>
                                </div>
                            </div>
                            <div className="mt-5 flex justify-end">
                                <DangerButton type="button" onClick={() => setConfirmingCleanup(true)}>
                                    Șterge jurnalele vechi
                                </DangerButton>
                            </div>
                        </div>
                    </section>
                </div>
            </div>

            <ConfirmDialog
                show={confirmingCleanup}
                onClose={() => setConfirmingCleanup(false)}
                onConfirm={handleCleanup}
                kicker="Zonă ireversibilă"
                title="Ștergi jurnalele vechi?"
                description="Sigur dorești să ștergi jurnalele vechi? Acțiunea nu poate fi anulată."
                cancelLabel="Renunță"
                confirmLabel="Șterge jurnalele vechi"
                processing={cleanupForm.processing}
            />
        </AppLayout>
    );
}
