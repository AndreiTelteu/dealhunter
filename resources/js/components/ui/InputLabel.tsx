import type { LabelHTMLAttributes, ReactElement } from 'react';

interface InputLabelProps extends LabelHTMLAttributes<HTMLLabelElement> {
    /** Convenience prop mirroring the Blade component's `value` attribute. */
    value?: string;
}

/** Port of x-input-label — engraved placard register. */
export function InputLabel({ value, children, className = '', ...props }: InputLabelProps): ReactElement {
    return (
        <label {...props} className={`block placard text-[0.65rem] ${className}`.trim()}>
            {value ?? children}
        </label>
    );
}
