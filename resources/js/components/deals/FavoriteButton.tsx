import { useEffect, useRef, useState, type ReactElement } from 'react';
import { router } from '@inertiajs/react';
import type { Errors } from '@inertiajs/core';
import type { SharedPageProps } from '../../types';

interface FavoriteButtonProps {
    /** Fully-built toggle URL serialized by the controller (route('deals.favorite.toggle')). */
    toggleUrl: string;
    /** Initial favorite state serialized by the controller (`isFavorite`). */
    initialFavorited: boolean;
    /** Compact square sizing for ledger rows (default) vs. detail surfaces. */
    className?: string;
}

/**
 * Inertia port of components/favorite-button.blade.php.
 *
 * Uses `router.post` with preserveState/preserveScroll so the surrounding
 * page props refresh via the shared partial reload. The heart flips
 * optimistically; `onError` restores the previous state and `onSuccess`
 * re-emits the authoritative server state and dispatches the
 * `favorites:updated` CustomEvent consumed by FavoritesBadge.
 */
export default function FavoriteButton({
    toggleUrl,
    initialFavorited,
    className = '',
}: FavoriteButtonProps): ReactElement {
    const [favorited, setFavorited] = useState(initialFavorited);
    const [busy, setBusy] = useState(false);
    /** Authoritative last-known state, used to restore on failure. */
    const lastConfirmed = useRef(initialFavorited);

    // Keep in sync when the page props themselves refresh (shared partial
    // reload after a successful toggle visit back()).
    useEffect(() => {
        setFavorited(initialFavorited);
        lastConfirmed.current = initialFavorited;
    }, [initialFavorited]);

    const handleToggle = (): void => {
        if (busy) {
            return;
        }

        const previous = lastConfirmed.current;
        const next = !previous;

        // Optimistic flip.
        setFavorited(next);
        setBusy(true);

        router.post(
            toggleUrl,
            {},
            {
                preserveState: true,
                preserveScroll: true,
                onSuccess: (page) => {
                    lastConfirmed.current = next;
                    const { favoritesCount } = page.props as unknown as SharedPageProps;

                    window.dispatchEvent(
                        new CustomEvent('favorites:updated', {
                            detail: { count: favoritesCount },
                        }),
                    );
                },
                onError: (_errors: Errors) => {
                    // Restore the confirmed state so the heart never lies.
                    setFavorited(previous);
                },
                onFinish: () => {
                    setBusy(false);
                },
            },
        );
    };

    return (
        <button
            type="button"
            onClick={handleToggle}
            disabled={busy}
            aria-pressed={favorited}
            aria-busy={busy}
            aria-label={favorited ? 'Elimină din favorite' : 'Adaugă la favorite'}
            className={`focus-ring inline-flex h-8 w-8 shrink-0 items-center justify-center rounded-sm border border-hairline bg-bench transition duration-150 ease-in-out hover:border-[#ff5d5d]/50 disabled:cursor-not-allowed disabled:opacity-60 ${className}`.trim()}
        >
            <svg
                className={`h-4 w-4 transition-colors ${favorited ? 'fill-[#ff5d5d] stroke-[#ff5d5d]' : 'fill-transparent stroke-dim'}`}
                style={{ transition: 'transform 0.15s ease, color 0.15s ease' }}
                xmlns="http://www.w3.org/2000/svg"
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
        </button>
    );
}
