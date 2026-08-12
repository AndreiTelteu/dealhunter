import type { ReactElement, ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import type { Deal } from '../../types';
import { formatNumber, truncate } from '../../lib/format';
import DealMetadata from './DealMetadata';

export type DealListRowVariant = 'dashboard' | 'ledger';

interface DealListRowProps {
    deal: Deal;
    /**
     * Row shape from the Blade pages:
     * - dashboard: compact row with inline meta + price (dashboard recent deals,
     *   hunted-deal show listings);
     * - ledger: wider registry row with a right-hand price readout column
     *   (deals index, favorites, hunted-deal associated deals).
     */
    variant?: DealListRowVariant;
    /** Media gallery slot (rendered before the content; Phase 3.5 component). */
    leading?: ReactNode;
    /** Actions rendered first in the badge row (e.g. the FavoriteButton). */
    leadingActions?: ReactNode;
    /** Extra verdict/meta badges appended after the standard ones. */
    metadata?: ReactNode;
    /** Title truncation length (60 dashboard, 100 deals index, 80 hunted). */
    titleLimit?: number;
    /** Description excerpt under the badges (deals index/favorites only). */
    description?: string | null;
    /** Meta line below badges — defaults mirror the Blade rows. */
    meta?: ReactNode;
    /** Right-hand block for the ledger variant (defaults to the price readout). */
    right?: ReactNode;
    /** Show the amber "Nou" badge (deals index / hunted listings). */
    showNew?: boolean;
}

function defaultMeta(deal: Deal, variant: DealListRowVariant): ReactNode {
    const searchLink = deal.huntedDealUrl ? (
        <Link href={deal.huntedDealUrl} className="text-beam hover:underline">
            {deal.searchTerm}
        </Link>
    ) : (
        <span className="text-dim">{deal.searchTerm}</span>
    );

    if (variant === 'dashboard') {
        return (
            <>
                {deal.priceAmount !== null && (
                    <>
                        <span className="text-[#eaf4f6]">
                            {formatNumber(deal.priceAmount)} {deal.priceCurrency ?? 'lei'}
                        </span>{' '}
                        &middot;{' '}
                    </>
                )}
                {deal.location && (
                    <>
                        {deal.location} &middot;{' '}
                    </>
                )}
                {deal.createdAt}
                <span className="mt-1 block break-words text-sm font-sans text-dim">
                    Căutare: {searchLink}
                </span>
            </>
        );
    }

    return (
        <>
            {deal.location && (
                <>
                    {deal.location} &middot;{' '}
                </>
            )}
            văzut {deal.lastSeenAt ?? 'fără dată'}
            {typeof deal.snapshotsCount === 'number' && (
                <>
                    {' '}
                    &middot; {deal.snapshotsCount}{' '}
                    {deal.snapshotsCount === 1 ? 'instantaneu' : 'instantanee'}
                </>
            )}{' '}
            &middot; căutare: {searchLink}
        </>
    );
}

function ledgerPriceBlock(deal: Deal): ReactElement {
    const hasPrice = deal.priceAmount !== null;

    return (
        <div className="text-right">
            <p className="placard text-[0.55rem]">Preț curent</p>
            <p className="mt-1 font-mono text-lg font-bold tabular-nums text-[#eaf4f6]">
                {hasPrice ? (
                    <>
                        {formatNumber(deal.priceAmount as number)} {deal.priceCurrency ?? 'lei'}
                    </>
                ) : (
                    <span className="text-sm font-normal text-dim">Fără preț</span>
                )}
            </p>
        </div>
    );
}

function ledgerActions(deal: Deal): ReactElement {
    return (
        <div className="flex shrink-0 items-center gap-4">
            <Link href={deal.showUrl} className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]">
                Detalii
            </Link>
            {deal.externalUrl && (
                <a
                    href={deal.externalUrl}
                    target="_blank"
                    rel="noopener"
                    className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                >
                    OLX &#8599;
                </a>
            )}
        </div>
    );
}

/**
 * Shared ledger row for deal collections. Mirrors the row markup shared by
 * dashboard recent deals, deals index, favorites and hunted-deal listings:
 * badges row (favorite slot + title + spectral verdicts), optional
 * description, mono meta line and variant-specific right column.
 * The media gallery and favorite button are injected through slots so
 * Phases 3.5/3.6 stay decoupled.
 */
export default function DealListRow({
    deal,
    variant = 'dashboard',
    leading,
    leadingActions,
    metadata,
    titleLimit = 60,
    description,
    meta,
    right,
    showNew = false,
}: DealListRowProps): ReactElement {
    const badgeRow = (
        <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
            {leadingActions}
            <Link
                href={deal.showUrl}
                className="focus-ring break-words rounded-sm font-sans font-semibold text-[#eaf4f6] transition-colors group-hover:text-beam"
            >
                {truncate(deal.title, titleLimit)}
            </Link>
            <DealMetadata
                deal={deal}
                showNew={showNew}
                dot={variant === 'ledger' ? 'spec' : 'glow'}
                showIntentScore={variant === 'ledger'}
            />
            {metadata}
        </div>
    );

    if (variant === 'ledger') {
        return (
            <article className="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
                <div className="grid gap-4 lg:grid-cols-[minmax(0,1fr)_11rem] lg:items-center lg:gap-8">
                    <div className="flex min-w-0 gap-4">
                        {leading}
                        <div className="min-w-0">
                            {badgeRow}
                            {description && (
                                <p className="mt-1 max-w-[68ch] text-sm text-dim">{description}</p>
                            )}
                            <p className="mt-2 font-mono text-[0.7rem] tabular-nums text-dim/70">
                                {meta ?? defaultMeta(deal, 'ledger')}
                            </p>
                        </div>
                    </div>
                    <div className="flex items-end justify-between gap-5 lg:flex-col lg:items-end lg:gap-3">
                        {right ?? ledgerPriceBlock(deal)}
                        {ledgerActions(deal)}
                    </div>
                </div>
            </article>
        );
    }

    return (
        <div className="group border-b border-hairline py-4 transition-colors hover:bg-bench/60">
            <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between sm:gap-6">
                <div className="flex min-w-0 flex-1 gap-4">
                    {leading}
                    <div className="min-w-0 flex-1">
                        {badgeRow}
                        {description && (
                            <p className="mt-1 max-w-[68ch] break-words text-sm text-dim">{description}</p>
                        )}
                        <p className="mt-1.5 font-mono text-[0.7rem] tabular-nums text-dim/70">
                            {meta ?? defaultMeta(deal, 'dashboard')}
                        </p>
                    </div>
                </div>
                <div className="flex shrink-0 items-center gap-4 sm:gap-5">{right ?? ledgerActions(deal)}</div>
            </div>
        </div>
    );
}
