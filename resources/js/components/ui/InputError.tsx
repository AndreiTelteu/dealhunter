import type { ReactElement } from 'react';

interface InputErrorProps {
    /** Server validation messages (e.g. `form.errors.email`, supports error bags). */
    message?: string | string[];
    className?: string;
}

/** Port of x-input-error — renders nothing without messages, like the Blade one. */
export function InputError({ message, className = '' }: InputErrorProps): ReactElement | null {
    if (!message) {
        return null;
    }

    const messages = Array.isArray(message) ? message : [message];

    if (messages.length === 0) {
        return null;
    }

    return (
        <ul className={`text-sm text-em-red space-y-1 ${className}`.trim()} role="alert">
            {messages.map((text, index) => (
                <li key={index}>{text}</li>
            ))}
        </ul>
    );
}
