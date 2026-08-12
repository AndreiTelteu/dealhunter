import type { ReactElement } from 'react';
import { usePage } from '@inertiajs/react';
import type { FlashMessages as FlashMessagesProps, SharedPageProps } from '../../types';

export type FlashVariant = keyof FlashMessagesProps;

const VARIANTS: Array<{ key: FlashVariant; lineClass: string; color: string }> = [
    { key: 'success', lineClass: 'flash-green', color: '#7dffa8' },
    { key: 'error', lineClass: 'flash-red', color: '#ff5d5d' },
    { key: 'info', lineClass: 'flash-beam', color: '#59e3ff' },
    { key: 'warning', lineClass: 'flash-amber', color: '#ffc46b' },
];

interface FlashLineProps {
    variant: FlashVariant;
    message: string;
    className?: string;
}

/** One emission line: color-coded verdict with the spectral line at the edge. */
export function FlashLine({ variant, message, className = '' }: FlashLineProps): ReactElement {
    const { lineClass, color } = VARIANTS.find(({ key }) => key === variant) ?? VARIANTS[2];

    return (
        <div className={`flash-line ${lineClass} ${className}`.trim()} role="alert">
            <span
                className="spec-line inline-block w-[2px] self-stretch"
                style={{ backgroundColor: color, color }}
                aria-hidden="true"
            ></span>
            <span className="block sm:inline">{message}</span>
        </div>
    );
}

interface FlashMessagesComponentProps {
    /** Override the shared prop (e.g. for testing); defaults to `usePage()` flash. */
    flash?: FlashMessagesProps;
    className?: string;
}

/**
 * Flash/status messages wired to the shared `flash` prop from
 * HandleInertiaRequests — success/error/info/warning as spectral lines.
 * Renders nothing when no message is present.
 */
export function FlashMessages({ flash, className = '' }: FlashMessagesComponentProps): ReactElement | null {
    const { props } = usePage<SharedPageProps>();
    const messages = flash ?? props.flash;

    const lines = VARIANTS.filter(({ key }) => messages[key]);

    if (lines.length === 0) {
        return null;
    }

    return (
        <div className={className.trim()} aria-live="polite">
            {lines.map(({ key }) => (
                <FlashLine key={key} variant={key} message={messages[key] as string} className="mb-3 last:mb-0" />
            ))}
        </div>
    );
}
