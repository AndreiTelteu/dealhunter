import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

export type StatAccent =
    | 'blue'
    | 'cyan'
    | 'green'
    | 'purple'
    | 'yellow'
    | 'amber'
    | 'red'
    | 'dim';

interface StatCardProps {
    title: string;
    value: string | number;
    accent?: StatAccent;
    href?: string;
    icon?: ReactNode;
    /** Extra classes merged onto the outer surface (grid spans etc.). */
    className?: string;
}

/**
 * Spectral readout: mono value + placard label + one thin emission line.
 * React port of the stat-card Blade component (legacy color names kept).
 */
const ACCENTS: Record<StatAccent, { hex: string; glow: string }> = {
    blue: { hex: '#59e3ff', glow: 'rgba(89,227,255,0.5)' },
    cyan: { hex: '#59e3ff', glow: 'rgba(89,227,255,0.5)' },
    green: { hex: '#7dffa8', glow: 'rgba(125,255,168,0.5)' },
    purple: { hex: '#59e3ff', glow: 'rgba(89,227,255,0.5)' },
    yellow: { hex: '#ffc46b', glow: 'rgba(255,196,107,0.5)' },
    amber: { hex: '#ffc46b', glow: 'rgba(255,196,107,0.5)' },
    red: { hex: '#ff5d5d', glow: 'rgba(255,93,93,0.5)' },
    dim: { hex: '#8fa8b0', glow: 'rgba(143,168,176,0.35)' },
};

export default function StatCard({
    title,
    value,
    accent = 'blue',
    href,
    icon,
    className = '',
}: StatCardProps) {
    const { hex, glow } = ACCENTS[accent];
    const surfaceClass = `group relative block bg-bench border border-hairline rounded-sm px-4 py-4 sm:px-5${className ? ` ${className}` : ''}`;

    const inner = (
        <>
            <div className="flex items-start justify-between gap-3">
                <div className="min-w-0">
                    <p className="placard text-[0.6rem] truncate">{title}</p>
                    <p className="mt-2.5 font-mono text-2xl sm:text-[1.7rem] leading-none font-medium tabular-nums text-[#eaf4f6]">
                        {value}
                    </p>
                </div>
                {icon && (
                    <span
                        className="shrink-0 text-dim/50 group-hover:text-beam transition-colors [&>svg]:h-4 [&>svg]:w-4"
                        aria-hidden="true"
                    >
                        {icon}
                    </span>
                )}
            </div>
            <span
                className="mt-3.5 block h-px w-full"
                style={{
                    background: hex,
                    opacity: 0.85,
                    boxShadow: `0 2px 4px rgba(0,0,0,0.7), 0 0 8px ${glow}`,
                }}
                aria-hidden="true"
            />
        </>
    );

    if (href) {
        return (
            <Link
                href={href}
                className={`${surfaceClass} hover:border-[rgba(89,227,255,0.35)] transition-colors focus-ring`}
            >
                {inner}
            </Link>
        );
    }

    return <div className={surfaceClass}>{inner}</div>;
}
