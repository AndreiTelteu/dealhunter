import type { ReactElement } from 'react';

interface StatusBadgeProps {
    status: string;
    totalErrors?: number;
    className?: string;
}

type BadgeState = 'healthy' | 'warning' | 'critical' | 'pending';

const LABELS: Record<string, string> = {
    healthy: 'În regulă',
    completed: 'Finalizată',
    warning: 'Atenție',
    partial: 'Parțială',
    critical: 'Critică',
    failed: 'Eșuată',
    started: 'Pornită',
    pending: 'Necunoscută',
};

const TEXT_COLORS: Record<BadgeState, string> = {
    healthy: 'text-em-green',
    warning: 'text-em-amber',
    critical: 'text-em-red',
    pending: 'text-beam',
};

const DOT_COLORS: Record<BadgeState, string> = {
    healthy: '#7dffa8',
    warning: '#ffc46b',
    critical: '#ff5d5d',
    pending: '#59e3ff',
};

function normalizeState(status: string, totalErrors: number): BadgeState {
    switch (status) {
        case 'healthy':
        case 'completed':
            return totalErrors > 0 ? 'warning' : 'healthy';
        case 'warning':
        case 'partial':
            return 'warning';
        case 'critical':
        case 'failed':
            return 'critical';
        default:
            return 'pending';
    }
}

function labelFor(status: string): string {
    if (LABELS[status]) {
        return LABELS[status];
    }

    return status.replace(/_/g, ' ').replace(/^\w/, (c) => c.toUpperCase());
}

/**
 * React port of components/admin/partials/status.blade.php — the status
 * verdict line: normalized state color, spectral dot and a localized label.
 */
export default function StatusBadge({
    status,
    totalErrors = 0,
    className = '',
}: StatusBadgeProps): ReactElement {
    const state = normalizeState(status, totalErrors);

    return (
        <span
            className={`inline-flex items-center gap-1.5 font-mono text-[0.6rem] uppercase ${TEXT_COLORS[state]} ${className}`.trim()}
        >
            <span
                className={`spec-line h-1.5 w-1.5 ${state === 'critical' ? 'alert-live' : ''}`}
                style={{ backgroundColor: DOT_COLORS[state] }}
                aria-hidden="true"
            />
            {labelFor(status)}
        </span>
    );
}
