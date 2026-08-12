import type { ButtonHTMLAttributes, ReactElement, ReactNode } from 'react';
import { Spinner } from './Spinner';

interface SecondaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    /** Button is mid-action (e.g. Inertia useForm `processing`); disables + shows spinner. */
    processing?: boolean;
}

/** Port of x-secondary-button (plain beamkey). Defaults to type="button". */
export function SecondaryButton({
    children,
    type = 'button',
    processing = false,
    disabled,
    ...props
}: SecondaryButtonProps): ReactElement {
    return (
        <button
            type={type}
            disabled={disabled || processing}
            {...props}
            className={`beamkey focus-ring rounded-sm px-4 py-2 text-xs disabled:opacity-40 disabled:pointer-events-none ${props.className ?? ''}`.trim()}
        >
            {processing && <Spinner />}
            {children}
        </button>
    );
}
