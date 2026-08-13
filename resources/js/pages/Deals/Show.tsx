import { useMemo, useState, type MouseEvent, type ReactElement } from 'react';
import { Link, usePage } from '@inertiajs/react';
import AppLayout from '../../layouts/AppLayout';
import DealMediaGallery from '../../components/deals/DealMediaGallery';
import FavoriteButton from '../../components/deals/FavoriteButton';
import { formatNumber } from '../../lib/format';
import type {
    DealCurrentReadout,
    DealDetail,
    DealSnapshot,
    HuntedDealSummary,
    SharedPageProps,
} from '../../types';

interface DealsShowPageProps extends SharedPageProps {
    deal: DealDetail;
    current: DealCurrentReadout;
    snapshots: DealSnapshot[];
    huntedDeal: HuntedDealSummary;
    links: {
        index: string;
    };
}

/** Price-trace geometry, mirrored from the Blade SVG builder. */
const CHART_WIDTH = 760;
const CHART_HEIGHT = 190;
const PAD_LEFT = 8;
const PAD_RIGHT = 8;
const PAD_TOP = 14;
const PAD_BOTTOM = 30;
const INNER_WIDTH = CHART_WIDTH - PAD_LEFT - PAD_RIGHT;
const INNER_HEIGHT = CHART_HEIGHT - PAD_TOP - PAD_BOTTOM;
const POP_WIDTH = 220;

const BEAM_CYAN = '#59e3ff';
const ALERT_RED = '#ff5d5d';

interface TracePoint {
    x: number;
    y: number;
    price: number;
    label: string;
    currency: string;
    capturedLabel: string;
}

interface TraceModel {
    points: TracePoint[];
    path: string;
    firstPrice: number;
    lastPrice: number;
    change: number;
    changePercent: number;
    hasPriceDrop: boolean;
    traceColor: string;
    minPrice: number;
    midPrice: number;
    maxPrice: number;
    firstLabel: string;
    lastLabel: string;
    firstCurrency: string;
    lastCurrency: string;
    ariaLabel: string;
}

const decimalFormat = new Intl.NumberFormat('ro-RO', {
    minimumFractionDigits: 1,
    maximumFractionDigits: 1,
});

/**
 * Build the price-trace model client-side from the server snapshots,
 * exactly the way the Blade @php block computed the SVG geometry.
 */
function buildTrace(priceSnapshots: DealSnapshot[]): TraceModel | null {
    if (priceSnapshots.length <= 1) {
        return null;
    }

    const prices = priceSnapshots.map((snapshot) => snapshot.priceAmount as number);
    const minPrice = Math.min(...prices);
    const maxPrice = Math.max(...prices);
    const priceRange = Math.max(maxPrice - minPrice, 1);
    const timestamps = priceSnapshots.map((snapshot) => new Date(snapshot.capturedAt).getTime() / 1000);
    const firstTimestamp = timestamps[0];
    const lastTimestamp = timestamps[timestamps.length - 1];
    const timeRange = Math.max(lastTimestamp - firstTimestamp, 1);

    const yFor = (value: number): number =>
        Math.round((PAD_TOP + (1 - (value - minPrice) / priceRange) * INNER_HEIGHT) * 10) / 10;

    const points: TracePoint[] = priceSnapshots.map((snapshot, index) => {
        const x = PAD_LEFT + ((timestamps[index] - firstTimestamp) / timeRange) * INNER_WIDTH;

        return {
            x: Math.round(x * 10) / 10,
            y: yFor(snapshot.priceAmount as number),
            price: snapshot.priceAmount as number,
            label: formatNumber(snapshot.priceAmount as number),
            currency: snapshot.priceCurrency ?? 'RON',
            capturedLabel: snapshot.capturedAtLabel,
        };
    });

    const path = 'M ' + points.map((point) => `${point.x} ${point.y}`).join(' L ');
    const firstPrice = prices[0];
    const lastPrice = prices[prices.length - 1];
    const change = lastPrice - firstPrice;
    const changePercent = firstPrice > 0 ? (change / firstPrice) * 100 : 0;
    const hasPriceDrop = change < 0;
    const traceColor = hasPriceDrop ? ALERT_RED : BEAM_CYAN;

    return {
        points,
        path,
        firstPrice,
        lastPrice,
        change,
        changePercent,
        hasPriceDrop,
        traceColor,
        minPrice,
        midPrice: minPrice + priceRange / 2,
        maxPrice,
        firstLabel: formatNumber(firstPrice),
        lastLabel: formatNumber(lastPrice),
        firstCurrency: priceSnapshots[0].priceCurrency ?? 'RON',
        lastCurrency: priceSnapshots[priceSnapshots.length - 1].priceCurrency ?? 'RON',
        ariaLabel: `Preț de la ${formatNumber(firstPrice)} la ${formatNumber(lastPrice)} ${
            priceSnapshots[priceSnapshots.length - 1].priceCurrency ?? 'RON'
        }.`,
    };
}

function monthDayLabel(isoTimestamp: string): string {
    return new Date(isoTimestamp).toLocaleDateString('ro-RO', { day: '2-digit', month: 'short' });
}

function PriceTrace({ priceSnapshots }: { priceSnapshots: DealSnapshot[] }): ReactElement {
    const trace = useMemo(() => buildTrace(priceSnapshots), [priceSnapshots]);
    const [active, setActive] = useState<TracePoint | null>(null);
    const [mouse, setMouse] = useState({ x: 0, y: 0 });
    const [containerRect, setContainerRect] = useState<DOMRect | null>(null);

    const handleMouseMove = (event: MouseEvent<HTMLDivElement>): void => {
        if (!trace) {
            return;
        }

        const rect = event.currentTarget.getBoundingClientRect();
        const px = (event.clientX - rect.left) * (CHART_WIDTH / rect.width);
        let best = trace.points[0];
        for (const sample of trace.points) {
            if (Math.abs(sample.x - px) < Math.abs(best.x - px)) {
                best = sample;
            }
        }

        setActive(best);
        setMouse({ x: event.clientX - rect.left, y: event.clientY - rect.top });
        setContainerRect(rect);
    };

    const park = (): void => {
        setActive(null);
    };

    if (!trace) {
        return (
            <>
                <div className="relative h-28" aria-hidden="true">
                    <div className="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                    <div className="beam-core beam-idle absolute left-1/2 top-0 bottom-0 w-[3px]"></div>
                </div>
                <p className="mt-3 text-sm text-dim">Sunt necesare cel puțin două prețuri pentru evoluție.</p>
            </>
        );
    }

    const yFor = (value: number): number => Math.round((PAD_TOP + (1 - (value - trace.minPrice) / Math.max(trace.maxPrice - trace.minPrice, 1)) * INNER_HEIGHT) * 10) / 10;

    const popLeft = containerRect !== null && mouse.x > containerRect.width * 0.62
        ? Math.max(0, mouse.x - POP_WIDTH - 18)
        : Math.min((containerRect?.width ?? POP_WIDTH) - POP_WIDTH, mouse.x + 18);
    const popTopValue = mouse.y - 68;
    const popTop = containerRect !== null
        ? Math.min(Math.max(4, popTopValue), Math.max(4, containerRect.height - 96))
        : popTopValue;

    const sign = trace.change > 0 ? '+' : '';

    return (
        <>
            <div
                className="relative cursor-crosshair"
                onMouseMove={handleMouseMove}
                onMouseLeave={park}
            >
                <svg
                    viewBox={`0 0 ${CHART_WIDTH} ${CHART_HEIGHT}`}
                    className="block w-full"
                    role="img"
                    aria-label={trace.ariaLabel}
                >
                    <line x1={PAD_LEFT} y1={yFor(trace.maxPrice)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(trace.maxPrice)} stroke="#1c242a" strokeWidth="1" />
                    <line x1={PAD_LEFT} y1={yFor(trace.midPrice)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(trace.midPrice)} stroke="#1c242a" strokeWidth="1" strokeDasharray="3 4" />
                    <line x1={PAD_LEFT} y1={yFor(trace.minPrice)} x2={CHART_WIDTH - PAD_RIGHT} y2={yFor(trace.minPrice)} stroke="#1c242a" strokeWidth="1" />
                    <path
                        d={trace.path}
                        fill="none"
                        stroke={trace.traceColor}
                        strokeWidth="1.6"
                        strokeLinecap="round"
                        strokeLinejoin="round"
                        style={{ filter: `drop-shadow(0 3px 3px rgba(0,0,0,.7)) drop-shadow(0 0 6px ${trace.traceColor})` }}
                    />
                    <circle cx={trace.points[0].x} cy={trace.points[0].y} r="2.6" fill="#06080a" stroke={trace.traceColor} strokeWidth="1.4" />
                    <circle
                        className={trace.hasPriceDrop ? 'alert-live' : ''}
                        cx={trace.points[trace.points.length - 1].x}
                        cy={trace.points[trace.points.length - 1].y}
                        r="3"
                        fill={trace.traceColor}
                        style={{ filter: `drop-shadow(0 0 5px ${trace.traceColor})` }}
                    />
                    {active && (
                        <g aria-hidden="true">
                            <line x1={active.x} y1={PAD_TOP} x2={active.x} y2={CHART_HEIGHT - PAD_BOTTOM} stroke="#8fa8b0" strokeOpacity="0.45" strokeWidth="1" strokeDasharray="2 3" />
                            <circle
                                cx={active.x}
                                cy={active.y}
                                r="3.6"
                                fill="#06080a"
                                stroke={trace.traceColor}
                                strokeWidth="1.6"
                                style={{ filter: `drop-shadow(0 0 5px ${trace.traceColor})` }}
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
                        <p className="placard text-[0.55rem]">Citire instantanee</p>
                        <p
                            className="mt-1.5 font-mono text-base font-bold tabular-nums"
                            style={{ color: trace.traceColor, textShadow: `0 2px 6px rgba(0,0,0,.7), 0 0 12px ${trace.traceColor}40` }}
                        >
                            <span>{active.label}</span>{' '}
                            <span className="text-[0.65rem] font-normal text-dim">{active.currency}</span>
                        </p>
                        <p className="mt-1 font-mono text-[0.62rem] tabular-nums text-dim/80">{active.capturedLabel}</p>
                    </div>
                )}
            </div>
            <div className="mt-1.5 flex justify-between font-mono text-[0.6rem] tabular-nums text-dim/60">
                <span>min {formatNumber(trace.minPrice)}</span>
                <span>mijloc {formatNumber(trace.midPrice)}</span>
                <span>max {formatNumber(trace.maxPrice)}</span>
            </div>
            <dl className="mt-5 grid grid-cols-2 gap-x-5 gap-y-4 border-t border-hairline pt-4 sm:grid-cols-4">
                <div>
                    <dt className="placard text-[0.55rem]">Primul</dt>
                    <dd className="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">{trace.firstLabel} {trace.firstCurrency}</dd>
                </div>
                <div>
                    <dt className="placard text-[0.55rem]">Ultimul</dt>
                    <dd className="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">{trace.lastLabel} {trace.lastCurrency}</dd>
                </div>
                <div>
                    <dt className="placard text-[0.55rem]">Variație</dt>
                    <dd className={`mt-1 font-mono text-sm tabular-nums ${trace.hasPriceDrop ? 'text-em-red' : 'text-beam'}`}>
                        {sign}{formatNumber(trace.change)} ({sign}{decimalFormat.format(trace.changePercent)}%)
                    </dd>
                </div>
                <div>
                    <dt className="placard text-[0.55rem]">Interval</dt>
                    <dd className="mt-1 font-mono text-sm tabular-nums text-[#eaf4f6]">
                        {monthDayLabel(priceSnapshots[0].capturedAt)} — {monthDayLabel(priceSnapshots[priceSnapshots.length - 1].capturedAt)}
                    </dd>
                </div>
            </dl>
        </>
    );
}

export default function DealsShow(): ReactElement {
    const { deal, current, snapshots, huntedDeal, links } = usePage<DealsShowPageProps>().props;

    const priceSnapshots = useMemo(
        () => snapshots.filter((snapshot) => snapshot.priceAmount !== null),
        [snapshots],
    );
    const hasClassification = current.matchesIntent !== null || current.likelyWorking !== null;
    const showPriceRaw =
        current.priceRaw !== null
        && current.priceRaw !== `${current.priceAmount} ${current.priceCurrency}`;

    const header = (
        <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
            <div className="min-w-0">
                <p className="placard mb-1.5 text-[0.6rem]">Fișa anunțului</p>
                <h2 className="font-sans text-xl font-bold leading-tight text-[#eaf4f6] sm:text-2xl">{current.title}</h2>
                <p className="mt-2 text-sm text-dim">
                    Căutare:{' '}
                    <Link href={huntedDeal.showUrl} prefetch="hover" className="text-beam hover:underline">
                        {huntedDeal.searchTerm}
                    </Link>
                </p>
            </div>
            <div className="flex shrink-0 flex-wrap items-center gap-2.5">
                <FavoriteButton toggleUrl={deal.toggleFavoriteUrl} initialFavorited={deal.isFavorite} />
                <Link href={links.index} className="beamkey focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]">
                    &larr; Anunțuri
                </Link>
                {deal.externalUrl && (
                    <a
                        href={deal.externalUrl}
                        target="_blank"
                        rel="noopener"
                        className="beamkey beamkey-armed focus-ring rounded-sm px-4 py-2.5 text-[0.65rem]"
                    >
                        Deschide OLX &#8599;
                    </a>
                )}
            </div>
        </div>
    );

    return (
        <AppLayout title="Fișa anunțului" header={header}>
            <div className="py-8 sm:py-10">
                <div className="mx-auto max-w-7xl space-y-8 px-4 sm:px-6 lg:px-8">
                    <section className="border border-hairline graticule" aria-labelledby="readout-heading">
                        <div className="grid lg:grid-cols-[minmax(0,1fr)_18rem]">
                            <div className="border-b border-hairline px-5 py-6 lg:border-b-0 lg:border-r">
                                <p className="placard text-[0.6rem]">Citire curentă</p>
                                <div className="mt-4 flex flex-wrap items-end justify-between gap-5">
                                    <div>
                                        <h3 id="readout-heading" className="font-sans text-lg font-bold text-[#eaf4f6]">Preț curent</h3>
                                        <p
                                            className="mt-1 font-mono text-3xl font-bold tabular-nums text-beam"
                                            style={{ textShadow: '0 3px 8px rgba(0,0,0,.7), 0 0 16px rgba(89,227,255,.35)' }}
                                        >
                                            {current.priceAmount !== null ? (
                                                <>
                                                    {formatNumber(current.priceAmount)} {current.priceCurrency}
                                                </>
                                            ) : (
                                                <span className="text-lg font-normal text-dim">Fără preț</span>
                                            )}
                                        </p>
                                        {showPriceRaw && (
                                            <p className="mt-1 font-mono text-[0.65rem] text-dim/70">text OLX: {current.priceRaw}</p>
                                        )}
                                    </div>
                                    <dl className="grid grid-cols-2 gap-x-8 gap-y-3 font-mono text-[0.7rem] tabular-nums">
                                        <div>
                                            <dt className="placard text-[0.55rem]">Instantaneu</dt>
                                            <dd className="mt-1 text-[#eaf4f6]">{current.snapshotCapturedAtLabel ?? 'fără captură'}</dd>
                                        </div>
                                        <div>
                                            <dt className="placard text-[0.55rem]">Ultima apariție</dt>
                                            <dd className="mt-1 text-[#eaf4f6]">{deal.lastSeenAtLabel ?? 'fără dată'}</dd>
                                        </div>
                                    </dl>
                                </div>
                            </div>
                            <div className="bg-bench px-5 py-6">
                                <p className="placard text-[0.6rem]">Clasificare</p>
                                <dl className="mt-4 space-y-3 font-mono text-[0.75rem] tabular-nums">
                                    {current.matchesIntent !== null && (
                                        <div className="flex items-center justify-between gap-4 border-b border-hairline pb-3">
                                            <dt className="text-dim">Potrivire</dt>
                                            <dd className={current.matchesIntent ? 'text-em-green' : 'text-em-amber'}>
                                                {current.matchesIntent ? 'Da' : 'Nu'}
                                                {current.intentScore !== null && <> ({current.intentScore}%)</>}
                                            </dd>
                                        </div>
                                    )}
                                    {current.likelyWorking !== null && (
                                        <div className="flex items-center justify-between gap-4 border-b border-hairline pb-3">
                                            <dt className="text-dim">Pare funcțional</dt>
                                            <dd className={current.likelyWorking ? 'text-em-green' : 'text-em-amber'}>
                                                {current.likelyWorking ? 'Da' : 'Nu'}
                                            </dd>
                                        </div>
                                    )}
                                    {current.confidence !== null && (
                                        <div className="flex items-center justify-between gap-4">
                                            <dt className="text-dim">Încredere</dt>
                                            <dd className="text-[#eaf4f6]">{Math.round(current.confidence * 100)}%</dd>
                                        </div>
                                    )}
                                    {!hasClassification && <div className="text-em-amber">Fără clasificare</div>}
                                </dl>
                            </div>
                        </div>
                    </section>

                    <section className="border border-hairline graticule" aria-labelledby="trace-heading">
                        <div className="flex flex-wrap items-end justify-between gap-3 border-b border-hairline px-5 py-4">
                            <div>
                                <p className="placard text-[0.6rem]">Istoric real</p>
                                <h3 id="trace-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Evoluția prețului</h3>
                            </div>
                            <p className="font-mono text-[0.65rem] tabular-nums text-dim/70">
                                {priceSnapshots.length} {priceSnapshots.length === 1 ? 'preț înregistrat' : 'prețuri înregistrate'}
                            </p>
                        </div>
                        <div className="px-5 py-6">
                            <PriceTrace priceSnapshots={priceSnapshots} />
                        </div>
                    </section>

                    <div className="grid gap-8 xl:grid-cols-[minmax(0,1fr)_20rem]">
                        <div className="space-y-8">
                            {current.description && (
                                <section className="border border-hairline bg-bench px-5 py-5" aria-labelledby="description-heading">
                                    <p className="placard text-[0.6rem]">Descriere OLX</p>
                                    <h3 id="description-heading" className="sr-only">Descriere</h3>
                                    <div className="mt-3 whitespace-pre-line text-sm leading-relaxed text-dim" style={{ maxWidth: '72ch' }}>
                                        {current.description}
                                    </div>
                                </section>
                            )}

                            {snapshots.length > 0 && (
                                <section aria-labelledby="history-heading">
                                    <div className="mb-4">
                                        <p className="placard text-[0.6rem]">Jurnal</p>
                                        <h3 id="history-heading" className="mt-1 font-sans text-lg font-bold text-[#eaf4f6]">Instantanee păstrate</h3>
                                    </div>
                                    <div className="border-t border-hairline">
                                        {snapshots.map((snapshot) => (
                                            <article key={snapshot.id} className="grid gap-3 border-b border-hairline py-4 sm:grid-cols-[10rem_minmax(0,1fr)]">
                                                <p className="font-mono text-[0.7rem] tabular-nums text-dim/70">{snapshot.capturedAtLabel}</p>
                                                <div className="min-w-0">
                                                    <div className="flex flex-wrap gap-x-4 gap-y-1 font-mono text-[0.7rem] tabular-nums">
                                                        <span className="text-[#eaf4f6]">
                                                            {snapshot.priceAmount !== null
                                                                ? `${formatNumber(snapshot.priceAmount)} ${snapshot.priceCurrency}`
                                                                : 'fără preț'}
                                                        </span>
                                                        {snapshot.location && <span className="text-dim">{snapshot.location}</span>}
                                                        {snapshot.sellerName && <span className="text-dim">{snapshot.sellerName}</span>}
                                                    </div>
                                                    {snapshot.title && snapshot.title !== current.title && (
                                                        <p className="mt-1 text-sm text-dim">{snapshot.title}</p>
                                                    )}
                                                </div>
                                            </article>
                                        ))}
                                    </div>
                                </section>
                            )}
                        </div>

                        <aside className="space-y-8">
                            <section className="border border-hairline bg-bench p-4" aria-labelledby="media-heading">
                                <p className="placard text-[0.6rem]">Media</p>
                                <h3 id="media-heading" className="sr-only">Imagini OLX</h3>
                                <DealMediaGallery deal={deal} mode="gallery" />
                            </section>

                            <section className="border border-hairline bg-bench px-5 py-5" aria-labelledby="metadata-heading">
                                <p className="placard text-[0.6rem]">Date anunț</p>
                                <h3 id="metadata-heading" className="sr-only">Date anunț</h3>
                                <dl className="mt-4 space-y-3 font-mono text-[0.7rem] tabular-nums">
                                    <div className="flex justify-between gap-4">
                                        <dt className="text-dim">Găsit</dt>
                                        <dd className="text-right text-[#eaf4f6]">{deal.createdAtLabel}</dd>
                                    </div>
                                    {current.postedAtLabel && (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-dim">Publicat</dt>
                                            <dd className="text-right text-[#eaf4f6]">{current.postedAtLabel}</dd>
                                        </div>
                                    )}
                                    {current.location && (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-dim">Locație</dt>
                                            <dd className="text-right text-[#eaf4f6]">{current.location}</dd>
                                        </div>
                                    )}
                                    {current.sellerName && (
                                        <div className="flex justify-between gap-4">
                                            <dt className="text-dim">Vânzător</dt>
                                            <dd className="text-right text-[#eaf4f6]">{current.sellerName}</dd>
                                        </div>
                                    )}
                                    {current.sellerUrl && (
                                        <div>
                                            <a
                                                href={current.sellerUrl}
                                                target="_blank"
                                                rel="noopener"
                                                className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.6rem]"
                                            >
                                                Profil vânzător &#8599;
                                            </a>
                                        </div>
                                    )}
                                </dl>
                            </section>

                            <section className="border border-hairline bg-bench px-5 py-5" aria-labelledby="search-heading">
                                <p className="placard text-[0.6rem]">Căutare asociată</p>
                                <h3 id="search-heading" className="mt-2 font-sans font-semibold text-[#eaf4f6]">{huntedDeal.searchTerm}</h3>
                                <p className={`mt-2 font-mono text-[0.7rem] ${huntedDeal.isActive ? 'text-em-green' : 'text-dim'}`}>
                                    {huntedDeal.isActive ? 'Activă' : 'În pauză'}
                                </p>
                                <Link href={huntedDeal.showUrl} prefetch="hover" className="rail-link focus-ring mt-4 rounded-sm border-b pb-0.5 text-[0.6rem]">
                                    Vezi căutarea &rarr;
                                </Link>
                            </section>
                        </aside>
                    </div>
                </div>
            </div>
        </AppLayout>
    );
}
