import type { ButtonHTMLAttributes, ReactElement, ReactNode } from 'react';
import { Spinner } from './Spinner';

interface PrimaryButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    /** Form is mid-submit (e.g. Inertia useForm `processing`); disables + shows spinner. */
    processing?: boolean;
}

/** Port of x-primary-button (beamkey armed). Defaults to type="submit". */
export function PrimaryButton({
    children,
    type = 'submit',
    processing = false,
    disabled,
    ...props
}: PrimaryButtonProps): ReactElement {
    return (
        <button
            type={type}
            disabled={disabled || processing}
            {...props}
            className={`beamkey beamkey-armed focus-ring rounded-sm px-5 py-2.5 text-xs disabled:pointer-events-none disabled:opacity-40 ${props.className ?? ''}`.trim()}
        >
            {processing && <Spinner />}
            {children}
        </button>
    );
}

export default PrimaryButton;
