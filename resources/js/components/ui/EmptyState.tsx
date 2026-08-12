import type { ReactElement, ReactNode } from 'react';

interface EmptyStateProps {
    /** Heading line (e.g. "Niciun anunț încă"). */
    title: string;
    /** Explanatory copy under the heading. */
    description?: ReactNode;
    /** Action node — a Link/button styled `beamkey`, rendered below the copy. */
    action?: ReactNode;
    className?: string;
}

/**
 * Port of the parked-beam empty state used by deals/hunted-deals listings:
 * graticule field, idle beam on the midline, heading, copy and one action.
 */
export function EmptyState({ title, description, action, className = '' }: EmptyStateProps): ReactElement {
    return (
        <div className={`relative border border-hairline graticule ${className}`.trim()}>
            <div className="px-6 py-14 text-center sm:py-16">
                <div className="relative mx-auto mb-8 h-16 max-w-md" aria-hidden="true">
                    <div className="absolute inset-x-0 top-1/2 h-px bg-[#1c242a]"></div>
                    <div className="beam-core beam-idle absolute bottom-0 left-1/2 top-0 w-[3px]"></div>
                </div>

                <h3 className="font-sans text-lg font-bold text-[#eaf4f6]">{title}</h3>

                {description && (
                    <p className="mx-auto mt-2 max-w-[52ch] text-sm text-dim">{description}</p>
                )}

                {action && <div className="mt-7">{action}</div>}
            </div>
        </div>
    );
}
