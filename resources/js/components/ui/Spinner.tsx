import type { ReactElement } from 'react';

interface SpinnerProps {
    className?: string;
}

/** Small beam-cyan spinner used by buttons in their loading/submit state. */
export function Spinner({ className = '' }: SpinnerProps): ReactElement {
    return (
        <svg
            className={`h-3.5 w-3.5 animate-spin ${className}`.trim()}
            viewBox="0 0 24 24"
            fill="none"
            aria-hidden="true"
        >
            <circle cx="12" cy="12" r="10" stroke="currentColor" strokeWidth="3" opacity="0.25" />
            <path d="M12 2a10 10 0 0 1 10 10" stroke="currentColor" strokeWidth="3" strokeLinecap="round" />
        </svg>
    );
}
