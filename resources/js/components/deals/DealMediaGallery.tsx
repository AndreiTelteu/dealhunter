import { useEffect, useId, useMemo, useRef, useState, type ReactElement } from 'react';
import { Fancybox } from '@fancyapps/ui';
import '@fancyapps/ui/dist/fancybox/fancybox.css';
import type { FancyboxOptions } from '@fancyapps/ui';
import type { Deal } from '../../types';

export type DealMediaGalleryMode = 'thumbnail' | 'gallery';

interface DealMediaGalleryProps {
    /**
     * The `media` prop arrives pre-filtered server-side to downloaded,
     * locally persisted files only (see DashboardController::serializeMedia
     * and the future Phase 4 deal serializers) — remote URLs never reach
     * this component and there is deliberately no remote fallback.
     */
    deal: Pick<Deal, 'id' | 'title' | 'media'>;
    /**
     * - thumbnail: listing surfaces (dashboard, deals index, favorites) —
     *   first image rendered as a small thumb, remaining images reachable
     *   through the same Fancybox group;
     * - gallery: the deals.show detail plate — full grid of images.
     */
    mode?: DealMediaGalleryMode;
}

/**
 * Mirrors the Blade-era `Fancybox.bind('[data-fancybox]', ...)` options:
 * classic thumbnail strip in the lightbox. Passed through the Carousel
 * options channel, which is how Fancybox v6 merges them onto its carousel.
 */
const fancyboxOptions: Partial<FancyboxOptions> = {
    Carousel: {
        Thumbs: {
            type: 'classic',
        },
    },
};

/**
 * Local-only deal media gallery. Mirrors the Blade
 * `components/deal-media-gallery` behaviour: one Fancybox group per gallery
 * instance, captions/alt text keyed to the deal title, broken images removed
 * on load error.
 *
 * Fancybox is bound inside the React lifecycle — scoped to this component's
 * container element and its unique group selector on mount, and unbound
 * again on unmount — so Inertia page swaps can never accumulate duplicate
 * document-level bindings.
 */
export default function DealMediaGallery({ deal, mode = 'thumbnail' }: DealMediaGalleryProps): ReactElement | null {
    const reactId = useId();
    /**
     * Unique, stable group name for this mounted gallery instance. All of
     * the deal's anchors share it, so Fancybox opens them as one gallery;
     * the instance-scoped suffix keeps two galleries for the same deal (or
     * remounts) from sharing a group.
     */
    const groupId = useMemo(
        () => `deal-${deal.id}-${reactId.replace(/[^a-zA-Z0-9_-]/g, '')}`,
        [deal.id, reactId],
    );

    const containerRef = useRef<HTMLDivElement>(null);
    const [failedIndexes, setFailedIndexes] = useState<ReadonlySet<number>>(new Set());

    const visibleMedia = useMemo(
        () =>
            deal.media
                .map((item, index) => ({ ...item, position: index + 1 }))
                .filter((item) => !failedIndexes.has(item.position - 1)),
        [deal.media, failedIndexes],
    );

    useEffect(() => {
        const container = containerRef.current;

        if (!container) {
            return;
        }

        const itemSelector = `[data-fancybox="${groupId}"]`;

        Fancybox.bind(container, itemSelector, fancyboxOptions);

        return () => {
            Fancybox.unbind(container, itemSelector);
        };
    }, [groupId]);

    if (visibleMedia.length === 0) {
        return null;
    }

    const handleImageError = (index: number): void => {
        setFailedIndexes((previous) => {
            const next = new Set(previous);
            next.add(index);

            return next;
        });
    };

    if (mode === 'thumbnail') {
        const [first, ...rest] = visibleMedia;

        return (
            <div className="shrink-0" ref={containerRef}>
                <a
                    href={first.url}
                    data-fancybox={groupId}
                    data-caption={`${deal.title} - imagine ${first.position}`}
                    className="focus-ring block h-16 w-16 overflow-hidden rounded-sm border border-hairline bg-[#06080a] sm:h-20 sm:w-20"
                >
                    <img
                        src={first.url}
                        alt={`${deal.title} - imagine ${first.position}`}
                        className="h-full w-full object-cover"
                        onError={() => handleImageError(first.position - 1)}
                    />
                </a>
                {rest.map((item) => (
                    <a
                        key={`${item.url}-${item.position}`}
                        href={item.url}
                        data-fancybox={groupId}
                        data-caption={`${deal.title} - imagine ${item.position}`}
                        className="sr-only"
                    >
                        Imagine {item.position}
                    </a>
                ))}
            </div>
        );
    }

    return (
        <div className="mt-3 grid grid-cols-2 gap-px bg-hairline" ref={containerRef}>
            {visibleMedia.map((item) => (
                <a
                    key={`${item.url}-${item.position}`}
                    href={item.url}
                    data-fancybox={groupId}
                    data-caption={`${deal.title} - imagine ${item.position}`}
                    className="focus-ring aspect-square bg-[#06080a]"
                >
                    <img
                        src={item.url}
                        alt={`${deal.title} - imagine ${item.position}`}
                        className="h-full w-full object-cover"
                        onError={() => handleImageError(item.position - 1)}
                    />
                </a>
            ))}
        </div>
    );
}
