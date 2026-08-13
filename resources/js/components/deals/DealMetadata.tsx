import type { ReactElement } from 'react';
import type { Deal } from '../../types';

interface DealMetadataProps {
    deal: Pick<Deal, 'matchesIntent' | 'intentScore' | 'likelyWorking' | 'isNew'>;
    /** Show the amber "Nou" marker for deals newer than 24 hours. */
    showNew?: boolean;
    /** Dot style: 'glow' (dashboard/hunted rows) or 'spec' (deals index/favorites). */
    dot?: 'glow' | 'spec';
    /** Show the NN% intent score next to "Potrivit" where it exists. */
    showIntentScore?: boolean;
}

function Dot({ dot }: { dot: 'glow' | 'spec' }): ReactElement {
    if (dot === 'spec') {
        return <span className="spec-line h-1 w-1 bg-[#7dffa8]" aria-hidden="true"></span>;
    }

    return (
        <span
            className="inline-block h-1 w-1 rounded-full bg-[#7dffa8]"
            style={{ boxShadow: '0 0 6px rgba(125,255,168,0.6)' }}
            aria-hidden="true"
        ></span>
    );
}

/**
 * Shared spectral verdict badges for deal rows: intent match, working
 * condition and the 24h-new marker. Mirrors the badge clusters used in
 * dashboard, deals index, favorites and hunted-deal listings.
 */
export default function DealMetadata({
    deal,
    showNew = false,
    dot = 'glow',
    showIntentScore = false,
}: DealMetadataProps): ReactElement | null {
    if (!deal.matchesIntent && !deal.likelyWorking && !(showNew && deal.isNew)) {
        return null;
    }

    return (
        <>
            {deal.matchesIntent && (
                <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                    <Dot dot={dot} />
                    Potrivit{showIntentScore && deal.intentScore !== null && deal.intentScore !== undefined
                        ? ` ${deal.intentScore}%`
                        : ''}
                </span>
            )}
            {deal.likelyWorking && (
                <span className="inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase text-em-green">
                    <Dot dot={dot} />
                    Funcțional
                </span>
            )}
            {showNew && deal.isNew && <span className="font-mono text-[0.6rem] uppercase text-em-amber">Nou</span>}
        </>
    );
}
