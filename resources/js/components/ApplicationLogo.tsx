import type { HTMLAttributes } from 'react';

interface ApplicationLogoProps extends HTMLAttributes<HTMLSpanElement> {
    withWordmark?: boolean;
}

/**
 * Deal Hunter brand mark (SVG cube + beam). Matches the Blade
 * application-logo component, animations handled by app.css
 * (.brand-mark__core / .brand-mark__beam).
 */
export default function ApplicationLogo({
    withWordmark = true,
    className = '',
    ...props
}: ApplicationLogoProps) {
    return (
        <span
            {...props}
            className={`flex items-center gap-2 ${className}`.trim()}
        >
            <svg
                className="h-14 w-14 shrink-0"
                viewBox="0 0 40 40"
                fill="none"
                aria-hidden="true"
            >
                <path
                    d="M6.5 14.5 20 7l13.5 7.5v14L20 36 6.5 28.5v-14Z"
                    fill="#0a0e11"
                    stroke="#59e3ff"
                    strokeWidth="1.5"
                />
                <path
                    d="M6.5 14.5 20 22l13.5-7.5M20 22v14"
                    stroke="#59e3ff"
                    strokeWidth="1.5"
                />
                <path
                    d="m13.5 18.35 4.2 2.3M26.5 18.35l-4.2 2.3"
                    stroke="#7dffa8"
                    strokeLinecap="round"
                    strokeWidth="2.2"
                />
                <circle className="brand-mark__core" cx="20" cy="22" r="2.2" fill="#59e3ff" />
                <path
                    className="brand-mark__beam"
                    d="M20 22v-5.2"
                    stroke="#59e3ff"
                    strokeLinecap="round"
                    strokeWidth="1.5"
                />
            </svg>
            {withWordmark && (
                <span className="brand-mark__wordmark font-sans text-base font-bold leading-none text-[#eaf4f6]">
                    Deal Hunter
                </span>
            )}
        </span>
    );
}
