import type { ButtonHTMLAttributes, ReactElement, ReactNode } from 'react';
import { Spinner } from './Spinner';

interface DangerButtonProps extends ButtonHTMLAttributes<HTMLButtonElement> {
    children: ReactNode;
    /** Form is mid-submit (e.g. Inertia useForm `processing`); disables + shows spinner. */
    processing?: boolean;
}

/** Port of x-danger-button (emission-red alert switch). Defaults to type="submit". */
export function DangerButton({
    children,
    type = 'submit',
    processing = false,
    disabled,
    ...props
}: DangerButtonProps): ReactElement {
    return (
        <button
            type={type}
            disabled={disabled || processing}
            {...props}
            className={`inline-flex items-center justify-center gap-2 px-4 py-2 rounded-sm border border-[rgba(255,93,93,0.45)] bg-[rgba(255,93,93,0.08)] font-mono uppercase text-xs text-[#ff5d5d] hover:bg-[rgba(255,93,93,0.16)] active:translate-y-[1px] focus-ring disabled:opacity-40 disabled:pointer-events-none transition ease-in-out duration-150 ${props.className ?? ''}`.trim()}
        >
            {processing && <Spinner />}
            {children}
        </button>
    );
}
