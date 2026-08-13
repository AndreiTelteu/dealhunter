import type { ReactElement } from 'react';

export type SpectrumLineState = 'green' | 'amber' | 'red';

interface SpectrumLineProps {
    /** Relative amplitude 0..1 — clamped to a 4% minimum like the Blade one. */
    height?: number;
    /** Emission verdict color (defaults to green/nominal). */
    state?: SpectrumLineState;
    /** Ignition delay in seconds, used for staggered beam passes. */
    delay?: number;
}

const COLORS: Record<SpectrumLineState, string> = {
    green: '#7dffa8',
    amber: '#ffc46b',
    red: '#ff5d5d',
};

/**
 * Port of components/spectrum-line.blade.php — one emission line rising
 * from the baseline of a spectrum field. Decorative: the parent field
 * carries the accessible reading (role="img" + aria-label).
 */
export default function SpectrumLine({ height = 0.5, state = 'green', delay = 0 }: SpectrumLineProps): ReactElement {
    const color = COLORS[state] ?? COLORS.green;
    const percent = Math.max(0.04, Math.min(1, height)) * 100;

    return (
        <div className="relative flex h-full justify-center" aria-hidden="true">
            <div
                className={`spec-line line-ignite absolute bottom-0 w-[3px] ${state === 'red' ? 'alert-live' : ''}`}
                style={{
                    height: `${percent}%`,
                    background: color,
                    color: color,
                    animationDelay: `${delay}s, ${delay}s`,
                }}
            ></div>
        </div>
    );
}
