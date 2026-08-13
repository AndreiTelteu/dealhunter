import { type ReactElement } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import type { AdminConfig, AdminConfigValue, SharedPageProps } from '../../types';

interface AdminConfigurationLinks {
    dashboard: string;
}

interface AdminConfigurationPageProps extends SharedPageProps {
    config: AdminConfig;
    links: AdminConfigurationLinks;
}

type ConfigGroupKey = keyof AdminConfig;

interface ConfigGroup {
    key: ConfigGroupKey;
    title: string;
    envPrefix: string;
}

const GROUPS: ConfigGroup[] = [
    { key: 'crawler', title: 'Parametri colectare', envPrefix: 'CRAWLER_' },
    { key: 'features', title: 'Funcții active', envPrefix: '' },
    { key: 'ai', title: 'Clasificare AI', envPrefix: 'AI_' },
    { key: 'currency', title: 'Monedă', envPrefix: 'CURRENCY_' },
];

function humanize(key: string): string {
    const spaced = key.replace(/([A-Z])/g, ' $1');

    return spaced.charAt(0).toUpperCase() + spaced.slice(1);
}

function envHint(prefix: string, key: string): string {
    return `${prefix}${key.replace(/([A-Z])/g, '_$1').toUpperCase()}`;
}

interface DisplayValue {
    text: string;
    isBoolean: boolean;
    booleanValue: boolean;
}

function displayValue(value: AdminConfigValue): DisplayValue {
    if (typeof value === 'boolean') {
        return { text: value ? 'Activată' : 'Dezactivată', isBoolean: true, booleanValue: value };
    }

    if (Array.isArray(value)) {
        return { text: JSON.stringify(value), isBoolean: false, booleanValue: false };
    }

    if (value === null || value === undefined) {
        return { text: 'Neconfigurată', isBoolean: false, booleanValue: false };
    }

    return { text: String(value), isBoolean: false, booleanValue: false };
}

export default function AdminConfiguration(): ReactElement {
    const { config, links } = usePage<AdminConfigurationPageProps>().props;

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <p className="placard mb-1.5 text-[0.6rem]">Administrare</p>
                <h2 className="font-sans text-xl font-bold text-[#eaf4f6] sm:text-2xl">Configurație</h2>
            </div>
            <Link href={links.dashboard} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                &larr; Panou administrare
            </Link>
        </div>
    );

    return (
        <AppLayout title="Configurație" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline bg-bench px-5 py-4">
                        <p className="placard text-[0.6rem]">Citire numai</p>
                        <p className="mt-2 text-sm text-dim" style={{ maxWidth: '72ch' }}>
                            Valorile sunt citite din variabilele de mediu și fișierele de configurare. Pentru modificare,
                            actualizează <code className="font-mono text-beam">.env</code> sau fișierul de configurare
                            corespunzător din <code className="font-mono text-beam">config/</code>.
                        </p>
                    </section>

                    {GROUPS.map((group) => (
                        <section key={group.key} className="border border-hairline" aria-labelledby={`${group.key}-heading`}>
                            <div className="border-b border-hairline bg-bench px-5 py-4">
                                <p className="placard text-[0.6rem]">Configurație</p>
                                <h3 id={`${group.key}-heading`} className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                    {group.title}
                                </h3>
                            </div>
                            <dl className="divide-y divide-hairline">
                                {Object.entries(config[group.key]).map(([key, value]) => {
                                    const display = displayValue(value);

                                    return (
                                        <div
                                            key={key}
                                            className="grid gap-2 px-5 py-4 sm:grid-cols-[minmax(0,1fr)_minmax(14rem,1fr)] sm:items-center"
                                        >
                                            <dt>
                                                <p className="font-sans text-sm font-semibold text-[#eaf4f6]">{humanize(key)}</p>
                                                <p className="mt-1 font-mono text-[0.65rem] text-dim/70">{envHint(group.envPrefix, key)}</p>
                                            </dt>
                                            <dd
                                                className={`break-all font-mono text-[0.75rem] tabular-nums ${
                                                    display.isBoolean
                                                        ? display.booleanValue
                                                            ? 'text-em-green'
                                                            : 'text-em-red'
                                                        : 'text-[#eaf4f6]'
                                                }`}
                                            >
                                                {display.text}
                                            </dd>
                                        </div>
                                    );
                                })}
                            </dl>
                        </section>
                    ))}

                    <section className="border border-hairline bg-bench" aria-labelledby="files-heading">
                        <div className="border-b border-hairline px-5 py-4">
                            <p className="placard text-[0.6rem]">Referință</p>
                            <h3 id="files-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">
                                Fișiere de configurare
                            </h3>
                        </div>
                        <div className="grid divide-y divide-hairline sm:grid-cols-2 sm:divide-x sm:divide-y-0">
                            <div className="p-5 text-sm text-dim">
                                <p>
                                    <code className="font-mono text-beam">config/crawler.php</code> — parametri colectare
                                </p>
                                <p className="mt-2">
                                    <code className="font-mono text-beam">config/features.php</code> — funcții active
                                </p>
                                <p className="mt-2">
                                    <code className="font-mono text-beam">config/ai.php</code> — clasificare AI
                                </p>
                                <p className="mt-2">
                                    <code className="font-mono text-beam">config/currency.php</code> — conversie valutară
                                </p>
                            </div>
                            <div className="p-5 text-sm text-dim">
                                <p>
                                    <code className="font-mono text-beam">.env</code> — configurația activă
                                </p>
                                <p className="mt-2">
                                    <code className="font-mono text-beam">.env.example</code> — șablon de configurare
                                </p>
                                <p className="mt-5 text-em-amber">
                                    După modificare, poate fi necesară repornirea aplicației și rularea{' '}
                                    <code className="font-mono">php artisan config:clear</code>.
                                </p>
                            </div>
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
