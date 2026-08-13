import { useMemo, useState, type ChangeEvent, type FormEvent, type MouseEvent, type ReactElement, type ReactNode } from 'react';
import { Link, router, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import Pagination from '../../components/Pagination';
import StatCard from '../../components/StatCard';
import DealListRow from '../../components/deals/DealListRow';
import DealMediaGallery from '../../components/deals/DealMediaGallery';
import FavoriteButton from '../../components/deals/FavoriteButton';
import { EmptyState } from '../../components/ui/EmptyState';
import { formatNumber } from '../../lib/format';
import type {
    Deal,
    DealFilterCounts,
    HuntedDealChart,
    HuntedDealChartSample,
    HuntedDealShowSummary,
    HuntedDealStats,
    Paginated,
    SharedPageProps,
} from '../../types';

interface HuntedDealsShowLinks {
    index: string;
    edit: string;
    dealsIndex: string;
    reset: string;
}

interface HuntedDealsShowFilters {
    search: string | null;
    sort: string;
    direction: string;
    priceDrops: boolean;
    newItems: boolean;
    matchesIntent: boolean;
    likelyWorking: boolean;
    hasActiveFilters: boolean;
}

interface HuntedDealsShowPageProps extends SharedPageProps {
    huntedDeal: HuntedDealShowSummary;
    deals: Paginated<Deal>;
    stats: HuntedDealStats;
    filterCounts: DealFilterCounts;
    filters: HuntedDealsShowFilters;
    chart: HuntedDealChart;
    links: HuntedDealsShowLinks;
}

/* ------------------------------------------------------------------ */
/* Price-spectrum trace (port of the Blade chart)                      */
/* ------------------------------------------------------------------ */

type Metric = 'min' | 'average' | 'max';

const METRIC_LABELS: Record<Metric, string> = {
    min: 'Preț minim',
    average: 'Medie de preț',
    max: 'Preț maxim',
};

const CHART_WIDTH = 760;
const CHART_HEIGHT = 190;
const PAD_LEFT = 8;
const PAD_RIGHT = 8;
const PAD_TOP = 14;
const PAD_BOTTOM = 30;
const INNER_WIDTH = CHART_WIDTH - PAD_LEFT - PAD_RIGHT;
const INNER_HEIGHT = CHART_HEIGHT - PAD_TOP - PAD_BOTTOM;
const POP_WIDTH = 230;

const BEAM_CYAN = '#59e3ff';
const ALERT_RED = '#ff5d5d';

interface ChartPoint {
    x: number;
    y: number;
    value: number;
    currency: string;
    captured: string;
    count: number;
}

interface ChartModel {
    points: ChartPoint[];
    path: string;
    min: number;
    mid: number;
    max: number;
    first: number;
    last: number;
    delta: number;
    traceColor: string;
}

function buildMetricModel(samples: HuntedDealChartSample[], metric: Metric): ChartModel {
    const values = samples.map((sample) => sample[metric]);
    const min = Math.min(...values);
    const max = Math.max(...values);
    const range = Math.max(max - min, 1);

    const firstTimestamp = samples[0].timestamp;
    const lastTimestamp = samples[samples.length - 1].timestamp;
    const timeRange = Math.max(lastTimestamp - firstTimestamp, 1);

    const yFor = (value: number): number =>
        Math.round((PAD_TOP + (1 - (value - min) / range) * INNER_HEIGHT) * 10) / 10;

    const points: ChartPoint[] = samples.map((sample) => ({
        x: Math.round((PAD_LEFT + ((sample.timestamp - firstTimestamp) / timeRange) * INNER_WIDTH) * 10) / 10,
        y: yFor(sample[metric]),
        value: sample[metric],
        currency: sample.currency,
        captured: sample.captured,
        count: sample.count,
    }));

    const path = 'M ' + points.map((point) => `${point.x.toFixed(1)} ${point.y.toFixed(1)}`).join(' L ');
    const first = values[0];
    const last = values[values.length - 1];
    const delta = last - first;
    const traceColor = delta < 0 ? ALERT_RED : BEAM_CYAN;

    return {
        points,
        path,
        min,
        mid: min + range / 2,
        max,
        first,
        last,
        delta,
        traceColor,
    };
}

function SpectrumTrace({ chart }: { chart: HuntedDealChart }): ReactElement {
    const [metric, setMetric] = useState<Metric>('min');
    const [active, setActive] = useState<ChartPoint | null>(null);
    const [mouse, setMouse] = useState({ x: 0, y: 0 });
    const [containerRect, setContainerRect] = useState<DOMRect | null>(null);

    const model = useMemo(() => buildMetricModel(chart.samples, metric), [chart.samples, metric]);

    const handleMouseMove = (event: MouseEvent<HTMLDivElement>): void => {
        const rect = event.currentTarget.getBoundingClientRect();
        const px = (event.clientX - rect.left) * (CHART_WIDTH / rect.width);
        let best = model.points[0];
        for (const point of model.points) {
            if (Math.abs(point.x - px) < Math.abs(best.x - px)) {
                best = point;
            }
        }

        setActive(best);
        setMouse({ x: event.clientX - rect.left, y: event.clientY - rect.top });
        setContainerRect(rect);
    };

    const park = (): void => {
        setActive(null);
    };

    const select = (next: Metric): void => {
        setMetric(next);
        setActive(null);
    };

    const popLeft =
        containerRect !== null && mouse.x > containerRect.width * 0.62
            ? Math.max(0, mouse.x - POP_WIDTH - 18)
            : Math.min((containerRect?.width ?? POP_WIDTH) - POP_WIDTH, mouse.x + 18);
    const popTopValue = mouse.y - 68;
    const popTop =
        containerRect !== null
            ? Math.min(Math.max(4, popTopValue), Math.max(4, containerRect.height - 96))
            : popTopValue;

    const yFor = (value: number): number =>
        Math.round((PAD_TOP + (1 - (value - model.min) / Math.max(model.max - model.min, 1)) * INNER_HEIGHT) * 10) / 10;

    const sign = model.delta > 0 ? '+' : '';
    const lastSample = chart.samples[chart.samples.length - 1];

    return (
        <div>
            <div className="mb-3 flex flex-wrap items-center gap-1.5 font-mono text-[0.6rem] tabular-nums" role="tablist" aria-label="Valoarea afișată în grafic">
                {(Object.keys(METRIC_LABELS) as Metric[]).map((key) => (
                    <button
                        key={key}
                        type="button"
                        role="tab"
                        aria-selected={metric === key}
                        onClick={() => select(key)}
                        className={`focus-ring rounded-sm border px-2.5 py-1.5 ${
                            metric === key
                                ? 'border-[#59e3ff]/70 bg-[#59e3ff]/10 text-beam'
                                : 'border-hairline text-dim hover:text-[#eaf4f6]'
                        }`}
                    >
                        {METRIC_LABELS[key]}
                    </button>
                ))}
                <span className="ml-1 text-dim/70">· {chart.currency}</span>
            </div>

            <div className="relative cursor-crosshair" onMouseMove={handleMouseMove} onMouseLeave={park}>
                <svg
                    viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`}
                    className="block h-auto w-full"
                    role="img"
                    aria-label={`${METRIC_LABELS[metric]}: de la ${formatNumber(model.first)} la ${formatNumber(model.last)} ${chart.currency}`}
                >
                    <line x1={PAD_LEFT} y1={yFor(model.max)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(model.max)} stroke="#1c242a" strokeWidth="1" />
                    <line x1={PAD_LEFT} y1={yFor(model.mid)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(model.mid)} stroke="#1c242a" strokeWidth="1" strokeDasharray="3 4" />
                    <line x1={PAD_LEFT} y1={yFor(model.min)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(model.min)} stroke="#1c242a" strokeWidth="1" />
                    <path
                        d={model.path}
                        fill="none"
                        stroke={model.traceColor}
                        strokeWidth="1.6"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        style={{ filter: `drop-shadow(0 2px 3px rgba(0,0,0,.7)) drop-shadow(0 0 6px ${model.traceColor})` }}
                    />
                    <circle cx={model.points[0].x} cy={model.points[0].y} r="2.6" fill="#06080a" stroke={model.traceColor} strokeWidth="1.4" />
                    <circle
                        cx={model.points[model.points.length - 1].x}
                        cy={model.points[model.points.length - 1].y}
                        r="3"
                        fill={model.traceColor}
                        style={{ filter: `drop-shadow(0 0 5px ${model.traceColor})` }}
                    />
                    {active && (
                        <g aria-hidden="true">
                            <line x1={active.x} y1={PAD_TOP} x2={active.x} y2={CHART_HEIGHT - PAD_BOTTOM} stroke="#8fa8b0" strokeOpacity="0.45" strokeWidth="1" strokeDasharray="2 3" />
                            <circle
                                cx={active.x}
                                cy={active.y}
                                r="3.6"
                                fill="#06080a"
                                stroke={model.traceColor}
                                strokeWidth="1.6"
                                style={{ filter: `drop-shadow(0 0 5px ${model.traceColor})` }}
                            />
                        </g>
                    )}
                </svg>

                {active && (
                    <div
                        className="pointer-events-none absolute z-10 border border-hairline bg-[#06080a] px-3.5 py-2.5"
                        style={{
                            left: `${popLeft}px`,
                            top: `${popTop}px`,
                            width: `${POP_WIDTH}px`,
                            boxShadow: '0 10px 24px rgba(0,0,0,.6), inset 0 0 0 1px rgba(89,227,255,.08)',
                        }}
                    >
                        <p className="placard text-[0.55rem]">{METRIC_LABELS[metric]}</p>
                        <p
                            className="mt-1.5 font-mono text-base font-bold tabular-nums"
                            style={{ color: model.traceColor, textShadow: `0 2px 6px rgba(0,0,0,.7), 0 0 12px ${model.traceColor}40` }}
                        >
                            <span>{formatNumber(active.value)}</span>{' '}
                            <span className="text-[0.65rem] font-normal text-dim">{active.currency}</span>
                        </p>
                        <p className="mt-1 font-mono text-[0.62rem] tabular-nums text-dim/80">
                            {active.captured} &middot; {active.count} anunțuri
                        </p>
                    </div>
                )}
            </div>

            <div className="mt-1.5 flex justify-between font-mono text-[0.6rem] tabular-nums text-dim/60">
                <span>{formatNumber(model.min)}</span>
                <span>{formatNumber(model.mid)}</span>
                <span>{formatNumber(model.max)}</span>
            </div>

            <dl className="mt-5 grid grid-cols-2 gap-x-4 gap-y-4 border-t border-hairline pt-4 sm:grid-cols-4">
                <div>
                    <dt className="placard text-[0.58rem]">Prima valoare</dt>
                    <dd className="mt-1.5 font-mono text-base tabular-nums text-[#eaf4f6]">
                        {formatNumber(model.first)} <span className="text-[0.65rem] text-dim/70">{chart.currency}</span>
                    </dd>
                </div>
                <div>
                    <dt className="placard text-[0.58rem]">Ultima valoare</dt>
                    <dd className="mt-1.5 font-mono text-base tabular-nums text-[#eaf4f6]">
                        {formatNumber(model.last)} <span className="text-[0.65rem] text-dim/70">{chart.currency}</span>
                    </dd>
                </div>
                <div>
                    <dt className="placard text-[0.58rem]">Variație</dt>
                    <dd className={`mt-1.5 font-mono text-base tabular-nums ${model.delta < 0 ? 'text-em-red' : 'text-em-green'}`}>
                        {`${sign}${formatNumber(model.delta)}`}
                    </dd>
                </div>
                <div>
                    <dt className="placard text-[0.58rem]">Min / Max serie</dt>
                    <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums leading-snug text-dim">
                        {formatNumber(model.min)}
                        <br />
                        {formatNumber(model.max)}
                    </dd>
                </div>
            </dl>

            <p className="mt-3 font-mono text-[0.62rem] tabular-nums text-dim/60">
                Ultimul instantaneu: {METRIC_LABELS[metric].toLowerCase()} din {lastSample.count} anunțuri potrivite cu preț &middot; {chart.lastCaptured}
            </p>
        </div>
    );
}

function SpectrumSection({ chart, stats }: { chart: HuntedDealChart; stats: HuntedDealStats }): ReactElement {
    const readings = [
        { label: 'Găsite', count: stats.totalDeals, color: '#59e3ff', value: stats.totalDeals },
        { label: 'Potr.', count: stats.matchingIntent, color: '#7dffa8', value: stats.matchingIntent },
        { label: 'Func.', count: stats.likelyWorking, color: '#7dffa8', value: stats.likelyWorking },
        { label: 'Preț', count: stats.priceDrops, color: '#ff5d5d', value: stats.priceDrops, alert: stats.priceDrops > 0 },
    ];
    const maxCount = Math.max(stats.totalDeals, 1);
    const lineHeight = (count: number): number => Math.max(6, (count / maxCount) * 100);

    return (
        <section aria-labelledby="spectrum-heading">
            <div className="border border-hairline graticule">
                <div className="flex flex-wrap items-end justify-between gap-2 border-b border-hairline px-5 pb-3 pt-4">
                    <div>
                        <h3 id="spectrum-heading" className="font-sans text-base font-bold text-[#eaf4f6] sm:text-lg">
                            Spectrul căutării
                        </h3>
                        <p className="mt-0.5 max-w-[60ch] text-sm text-dim">
                            {chart.hasTrace
                                ? 'Media, minimul sau maximul de preț al anunțurilor potrivite, instantaneu cu instantaneu.'
                                : 'Cum citește fasciculul această căutare.'}
                        </p>
                    </div>
                    {chart.hasTrace && (
                        <p className="font-mono text-[0.65rem] tabular-nums text-dim/70">
                            {chart.sampleCount} instantanee &middot; {chart.firstCaptured} &ndash; {chart.lastCaptured}
                        </p>
                    )}
                </div>

                <div className="px-5 py-6">
                    <div className="grid grid-cols-1 items-stretch gap-8 lg:grid-cols-[1fr_15rem]">
                        <div className="min-w-0">
                            {chart.hasTrace ? (
                                <SpectrumTrace chart={chart} />
                            ) : chart.latestSnapshot ? (
                                <>
                                    <p className="placard mb-3 text-[0.6rem]">Ultimul instantaneu de preț</p>
                                    <div className="relative min-h-28 overflow-hidden border border-hairline bg-[#080c0f] px-5 py-4">
                                        <div className="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]" aria-hidden="true"></div>
                                        <div className="beam-core beam-idle absolute bottom-3 left-5 top-3 w-[3px]" aria-hidden="true"></div>
                                        <div className="relative ml-7">
                                            <dl className="grid grid-cols-1 gap-3 font-mono tabular-nums sm:grid-cols-3">
                                                <div>
                                                    <dt className="placard text-[0.52rem]">Preț minim</dt>
                                                    <dd className="mt-1 text-lg text-beam">
                                                        {formatNumber(chart.latestSnapshot.minPrice)}{' '}
                                                        <span className="text-[0.65rem] font-normal text-dim">{chart.latestSnapshot.priceCurrency}</span>
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="placard text-[0.52rem]">Medie de preț</dt>
                                                    <dd className="mt-1 text-lg text-[#eaf4f6]">
                                                        {formatNumber(chart.latestSnapshot.averagePrice)}{' '}
                                                        <span className="text-[0.65rem] font-normal text-dim">{chart.latestSnapshot.priceCurrency}</span>
                                                    </dd>
                                                </div>
                                                <div>
                                                    <dt className="placard text-[0.52rem]">Preț maxim</dt>
                                                    <dd className="mt-1 text-lg text-[#eaf4f6]">
                                                        {formatNumber(chart.latestSnapshot.maxPrice)}{' '}
                                                        <span className="text-[0.65rem] font-normal text-dim">{chart.latestSnapshot.priceCurrency}</span>
                                                    </dd>
                                                </div>
                                            </dl>
                                            <p className="mt-3 font-mono text-[0.65rem] tabular-nums text-dim/80">
                                                {chart.latestSnapshot.capturedAt} &middot; {chart.latestSnapshot.dealsCount} anunțuri potrivite cu preț
                                            </p>
                                            <p className="mt-3 text-sm text-dim">Acesta este primul reper. Trasarea evoluției apare după următorul instantaneu.</p>
                                        </div>
                                    </div>
                                </>
                            ) : (
                                <>
                                    <p className="placard mb-3 text-[0.6rem]">Medie de preț</p>
                                    <div className="relative flex h-28 items-center" aria-hidden="true">
                                        <div className="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                                        <div className="beam-core beam-idle absolute bottom-0 top-0 left-1/2 w-[3px]"></div>
                                    </div>
                                    <p className="mt-3 max-w-[52ch] text-sm text-dim">
                                        Încă nu există un instantaneu agregat. Media, minimul și maximul vor apărea după următoarea colectare.
                                    </p>
                                </>
                            )}
                        </div>

                        <div className="min-w-0 lg:border-l lg:border-hairline lg:pl-6">
                            <p className="placard mb-3 text-[0.6rem]">Lecturi</p>
                            <div
                                className="flex h-28 items-end gap-5"
                                role="img"
                                aria-label={`Lecturi spectrale: ${stats.totalDeals} anunțuri găsite, ${stats.matchingIntent} potrivite, ${stats.likelyWorking} pare funcționale, ${stats.priceDrops} schimbări de preț.`}
                            >
                                {readings.map((reading) => (
                                    <div key={reading.label} className="flex flex-1 flex-col items-center gap-2">
                                        <div
                                            className={`w-[3px] spec-line ${reading.alert ? 'alert-live' : ''}`}
                                            style={{ height: `${lineHeight(reading.count)}px`, background: reading.color, color: reading.color }}
                                            aria-hidden="true"
                                        ></div>
                                        <span className="font-mono text-[0.55rem] uppercase text-dim/70">{reading.label}</span>
                                    </div>
                                ))}
                            </div>
                            <dl className="mt-4 space-y-2 border-t border-hairline pt-3">
                                <div className="flex justify-between font-mono text-[0.7rem] tabular-nums">
                                    <dt className="text-dim">Găsite</dt>
                                    <dd className="text-beam">{stats.totalDeals}</dd>
                                </div>
                                <div className="flex justify-between font-mono text-[0.7rem] tabular-nums">
                                    <dt className="text-dim">Potrivite</dt>
                                    <dd className="text-em-green">{stats.matchingIntent}</dd>
                                </div>
                                <div className="flex justify-between font-mono text-[0.7rem] tabular-nums">
                                    <dt className="text-dim">Funcționale</dt>
                                    <dd className="text-em-green">{stats.likelyWorking}</dd>
                                </div>
                                <div className="flex justify-between font-mono text-[0.7rem] tabular-nums">
                                    <dt className="text-dim">Schimb. preț</dt>
                                    <dd className={stats.priceDrops > 0 ? 'text-em-red' : 'text-dim/70'}>{stats.priceDrops}</dd>
                                </div>
                            </dl>
                        </div>
                    </div>
                </div>
            </div>
        </section>
    );
}

/* ------------------------------------------------------------------ */
/* Page                                                                */
/* ------------------------------------------------------------------ */

const FIELD_CLASS =
    'mt-2 block w-full rounded-none border-hairline bg-[#06080a] px-3 py-2.5 text-sm text-[#eaf4f6] shadow-none placeholder:text-dim/50 focus:border-[#59e3ff]/60 focus:ring-2 focus:ring-[#59e3ff]/30';

const SORT_OPTIONS = [
    { value: 'last_seen_at', label: 'Ultima apariție' },
    { value: 'created_at', label: 'Adăugat' },
    { value: 'title', label: 'Titlu' },
    { value: 'price_amount', label: 'Preț' },
    { value: 'location', label: 'Locație' },
] as const;

function dealMeta(deal: Deal): ReactNode {
    return (
        <>
            {deal.priceAmount !== null ? (
                <>
                    <span className="text-[#eaf4f6]">
                        {formatNumber(deal.priceAmount)} {deal.priceCurrency ?? 'lei'}
                    </span>{' '}
                    &middot;{' '}
                </>
            ) : (
                <>
                    <span>Fără preț</span> &middot;{' '}
                </>
            )}
            {deal.location && (
                <>
                    {deal.location} &middot;{' '}
                </>
            )}
            {deal.createdAt}
        </>
    );
}

export default function HuntedDealsShow(): ReactElement {
    const { huntedDeal, deals, stats, filterCounts, filters, chart, links } = usePage<HuntedDealsShowPageProps>().props;

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
            huntedDeal.showUrl,
            {
                ...(search.trim() !== '' ? { search } : {}),
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
        <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div className="min-w-0">
                <p className="placard mb-1.5 text-[0.6rem]">Căutare urmărită</p>
                <h2 className="break-words font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">{huntedDeal.searchTerm}</h2>
                <div className="mt-2 flex flex-wrap items-center gap-x-4 gap-y-1.5">
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
                    <span className="font-mono text-[0.7rem] tabular-nums text-dim/70">
                        {huntedDeal.lastCrawledAt ? `Ultima verificare ${huntedDeal.lastCrawledAt}` : 'Neverificată încă'}
                    </span>
                </div>
            </div>
            <div className="flex shrink-0 flex-wrap items-center gap-2.5">
                <Link href={links.index} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Toate căutările
                </Link>
                <Link href={links.edit} className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    Editează
                </Link>
            </div>
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

    const emptyState = filters.hasActiveFilters ? (
        <EmptyState
            title="Niciun anunț nu corespunde"
            description="Schimbă termenul sau filtrele și încearcă din nou."
            action={
                <Link href={links.reset} className="beamkey focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                    Resetează filtrele
                </Link>
            }
        />
    ) : (
        <EmptyState
            title="Niciun anunț găsit încă"
            description={
                huntedDeal.isActive
                    ? `Căutarea este activă. Verificarea automată caută „${huntedDeal.searchTerm}" pe OLX România la următoarea rulare, iar anunțurile apar aici.`
                    : 'Această căutare este în pauză, așa că nu adună anunțuri. Activeaz-o pentru a începe urmărirea.'
            }
            action={
                !huntedDeal.isActive ? (
                    <Link href={links.edit} className="beamkey beamkey-armed focus-ring rounded-sm px-6 py-3 text-[0.7rem]">
                        Activează căutarea
                    </Link>
                ) : undefined
            }
        />
    );

    return (
        <AppLayout title={huntedDeal.searchTerm} header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section aria-label="Statistici căutare">
                        <div className="grid grid-cols-2 gap-3 sm:gap-4 lg:grid-cols-5">
                            <StatCard title="Anunțuri găsite" value={stats.totalDeals} accent="cyan" />
                            <StatCard title="Noi în 24h" value={stats.newDeals24h} accent={stats.newDeals24h > 0 ? 'amber' : 'dim'} />
                            <StatCard title="Potrivite" value={stats.matchingIntent} accent={stats.matchingIntent > 0 ? 'green' : 'dim'} />
                            <StatCard title="Pare funcționale" value={stats.likelyWorking} accent={stats.likelyWorking > 0 ? 'green' : 'dim'} />
                            <StatCard title="Schimbări de preț" value={stats.priceDrops} accent={stats.priceDrops > 0 ? 'red' : 'dim'} />
                        </div>
                    </section>

                    {huntedDeal.notes && (
                        <section aria-labelledby="notes-heading">
                            <div className="border border-hairline bg-bench px-5 py-4">
                                <h3 id="notes-heading" className="placard mb-2.5 text-[0.65rem]">Notițe</h3>
                                <p className="whitespace-pre-wrap break-words text-sm text-dim" style={{ maxWidth: '72ch' }}>
                                    {huntedDeal.notes}
                                </p>
                            </div>
                        </section>
                    )}

                    <SpectrumSection chart={chart} stats={stats} />

                    <section aria-labelledby="deals-heading">
                        <div className="mb-4 flex flex-wrap items-end justify-between gap-3">
                            <div>
                                <h3 id="deals-heading" className="font-sans text-lg font-bold text-[#eaf4f6] sm:text-xl">Anunțuri găsite</h3>
                                <p className="mt-1 max-w-[56ch] text-sm text-dim">Anunțurile de pe OLX România care se potrivesc acestei căutări.</p>
                            </div>
                            {deals.meta.total > 0 && (
                                <Link href={links.dealsIndex} className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]">
                                    Toate în Anunțuri &rarr;
                                </Link>
                            )}
                        </div>

                        <form onSubmit={handleSubmit} className="mb-6 border border-hairline bg-bench px-5 py-5" aria-label="Filtre anunțuri">
                            <div className="grid grid-cols-1 gap-4 lg:grid-cols-[minmax(0,1fr)_12rem_9rem]">
                                <div>
                                    <label htmlFor="search" className="placard text-[0.6rem]">Caută în anunțuri</label>
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
                                    <label htmlFor="sort" className="placard text-[0.6rem]">Ordine registru</label>
                                    <select id="sort" name="sort" value={sort} onChange={(event) => setSort(event.target.value)} className={FIELD_CLASS}>
                                        {SORT_OPTIONS.map((option) => (
                                            <option key={option.value} value={option.value}>
                                                {option.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <label htmlFor="direction" className="placard text-[0.6rem]">Sens</label>
                                    <select id="direction" name="direction" value={direction} onChange={(event) => setDirection(event.target.value)} className={FIELD_CLASS}>
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
                                <Link href={links.reset} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Resetează</Link>
                                <button type="submit" className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">Aplică</button>
                            </div>
                        </form>

                        {deals.data.length > 0 ? (
                            <>
                                <div className="border-t border-hairline">
                                    {deals.data.map((deal) => (
                                        <DealListRow
                                            key={deal.id}
                                            deal={deal}
                                            variant="dashboard"
                                            titleLimit={80}
                                            description={deal.description}
                                            showNew={deal.isNew ?? false}
                                            showIntentScore
                                            meta={dealMeta(deal)}
                                            leading={<DealMediaGallery deal={deal} mode="thumbnail" />}
                                            leadingActions={
                                                <FavoriteButton toggleUrl={deal.toggleFavoriteUrl} initialFavorited={deal.isFavorite} />
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
                        ) : (
                            emptyState
                        )}
                    </section>

                    <section aria-labelledby="meta-heading">
                        <div className="border border-hairline bg-bench px-5 py-5">
                            <h3 id="meta-heading" className="placard mb-4 text-[0.65rem]">Informații căutare</h3>
                            <dl className="grid grid-cols-2 gap-x-4 gap-y-5 lg:grid-cols-4">
                                <div>
                                    <dt className="placard text-[0.58rem]">Creată</dt>
                                    <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.createdAt}</dd>
                                    <dd className="font-mono text-[0.68rem] tabular-nums text-dim/70">{huntedDeal.createdAtTime}</dd>
                                </div>
                                <div>
                                    <dt className="placard text-[0.58rem]">Actualizată</dt>
                                    <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.updatedAt}</dd>
                                    <dd className="font-mono text-[0.68rem] tabular-nums text-dim/70">{huntedDeal.updatedAtTime}</dd>
                                </div>
                                <div>
                                    <dt className="placard text-[0.58rem]">Ultima verificare</dt>
                                    {huntedDeal.lastCrawledAtDate ? (
                                        <>
                                            <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-[#eaf4f6]">{huntedDeal.lastCrawledAtDate}</dd>
                                            <dd className="font-mono text-[0.68rem] tabular-nums text-dim/70">{huntedDeal.lastCrawledAtTime}</dd>
                                        </>
                                    ) : (
                                        <dd className="mt-1.5 font-mono text-[0.8rem] tabular-nums text-em-amber">Neverificată</dd>
                                    )}
                                </div>
                                <div>
                                    <dt className="placard text-[0.58rem]">Stare</dt>
                                    <dd className="mt-1.5">
                                        {huntedDeal.isActive ? (
                                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.7rem] uppercase text-em-green">
                                                <span
                                                    className="inline-block h-1 w-1 rounded-full bg-[#7dffa8]"
                                                    style={{ boxShadow: '0 0 6px rgba(125,255,168,0.6)' }}
                                                    aria-hidden="true"
                                                ></span>
                                                Activă
                                            </span>
                                        ) : (
                                            <span className="inline-flex items-center gap-1.5 font-mono text-[0.7rem] uppercase text-dim/70">
                                                <span className="inline-block h-1 w-1 rounded-full bg-[#1c242a]" aria-hidden="true"></span>
                                                În pauză
                                            </span>
                                        )}
                                    </dd>
                                </div>
                            </dl>
                        </div>
                    </section>
                </div>
            </div>
        </AppLayout>
    );
}
