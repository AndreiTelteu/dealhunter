import { useEffect, useState } from 'react';
import { Link } from '@inertiajs/react';
import { routes } from '../routes';

interface FavoritesBadgeProps {
    count: number;
    compact?: boolean;
}

/**
 * Port of the favorites badge in layouts/navigation.blade.php.
 * Keeps listening for the `favorites:updated` CustomEvent emitted by
 * favorite-toggle interactions (same contract as the Blade version).
 */
export default function FavoritesBadge({ count: initialCount, compact = false }: FavoritesBadgeProps) {
    const [count, setCount] = useState(initialCount);

    useEffect(() => {
        setCount(initialCount);
    }, [initialCount]);

    useEffect(() => {
        const handleUpdated = (event: Event): void => {
            const detail = (event as CustomEvent<{ count?: number }>).detail;

            if (typeof detail?.count === 'number') {
                setCount(detail.count);
            }
        };

        window.addEventListener('favorites:updated', handleUpdated);

        return () => window.removeEventListener('favorites:updated', handleUpdated);
    }, []);

    return (
        <Link
            href={routes.favoritesIndex}
            aria-label="Favorite"
            className={`focus-ring inline-flex h-9 items-center gap-2 rounded-sm border border-hairline bg-bench font-mono text-xs text-dim transition duration-150 ease-in-out hover:border-[#ff5d5d]/50 hover:text-[#ff5d5d] ${
                compact ? 'px-2.5' : 'px-3'
            }`}
        >
            <svg
                className={`h-4 w-4 stroke-[#ff5d5d] ${count > 0 ? 'fill-[#ff5d5d]' : 'fill-transparent'}`}
                viewBox="0 0 24 24"
                strokeWidth="1.5"
                aria-hidden="true"
            >
                <path
                    strokeLinecap="round"
                    strokeLinejoin="round"
                    d="M21 8.25c0-2.485-2.099-4.5-4.688-4.5-1.935 0-3.597 1.126-4.312 2.733-.715-1.607-2.377-2.733-4.313-2.733C5.1 3.75 3 5.765 3 8.25c0 7.22 9 12 9 12s9-4.78 9-12Z"
                />
            </svg>
            <span className={`tabular-nums ${count === 0 ? 'hidden' : ''}`}>{count}</span>
        </Link>
    );
}
