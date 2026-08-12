import type { ReactElement, ReactNode } from 'react';
import { Link } from '@inertiajs/react';
import type { Deal } from '../../types';
import { formatNumber } from '../../lib/format';
import DealMetadata from './DealMetadata';

interface DealCardProps {
    deal: Deal;
    /** Media thumbnail/gallery slot rendered at the top of the card. */
    media?: ReactNode;
    /** Action node rendered in the badge row (e.g. the FavoriteButton). */
    leadingActions?: ReactNode;
    /** Extra nodes appended after the standard verdict badges. */
    metadata?: ReactNode;
    /** Show the amber "Nou" badge when the deal is newer than 24 hours. */
    showNew?: boolean;
    /** Footer actions; defaults to the Detalii / OLX rail links. */
    actions?: ReactNode;
}

/**
 * Card-shaped deal readout for grid-style surfaces (Phase 4 galleries and
 * welcome specimens). Same instrument-panel grammar as the ledger rows:
 * bench surface, hairline frame, mono price readout and spectral verdict
 * badges. Media and favorite slots stay injectable so Phases 3.5/3.6
 * components drop in without touching this primitive.
 */
export default function DealCard({
    deal,
    media,
    leadingActions,
    metadata,
    showNew = false,
    actions,
}: DealCardProps): ReactElement {
    return (
        <article className="group flex flex-col border border-hairline bg-bench transition-colors hover:border-[rgba(89,227,255,0.35)]">
            {media}

            <div className="flex flex-1 flex-col gap-2.5 px-4 py-4 sm:px-5">
                <div className="flex flex-wrap items-center gap-x-3 gap-y-1.5">
                    {leadingActions}
                    <DealMetadata deal={deal} showNew={showNew} dot="glow" />
                </div>

                <Link
                    href={deal.showUrl}
                    className="focus-ring break-words rounded-sm font-sans font-semibold text-[#eaf4f6] transition-colors group-hover:text-beam"
                >
                    {deal.title}
                </Link>

                <div className="mt-auto flex items-end justify-between gap-4 pt-1">
                    <div>
                        <p className="placard text-[0.55rem]">Preț curent</p>
                        <p className="mt-1 font-mono text-lg font-bold tabular-nums text-[#eaf4f6]">
                            {deal.priceAmount !== null ? (
                                <>
                                    {formatNumber(deal.priceAmount)} {deal.priceCurrency ?? 'lei'}
                                </>
                            ) : (
                                <span className="text-sm font-normal text-dim">Fără preț</span>
                            )}
                        </p>
                    </div>

                    <div className="flex shrink-0 items-center gap-4">
                        {actions ?? (
                            <>
                                <Link
                                    href={deal.showUrl}
                                    className="rail-link focus-ring rounded-sm border-b pb-0.5 text-[0.65rem]"
                                >
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
                            </>
                        )}
                    </div>
                </div>

                {deal.location && (
                    <p className="font-mono text-[0.7rem] tabular-nums text-dim/70">{deal.location}</p>
                )}
                {metadata}
            </div>
        </article>
    );
}
