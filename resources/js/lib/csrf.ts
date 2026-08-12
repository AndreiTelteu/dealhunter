/**
 * CSRF token helper for direct fetch() calls (mirrors the Blade
 * components that read the <meta name="csrf-token"> tag).
 */
export function csrfToken(): string {
    const meta = document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]');

    return meta?.content ?? '';
}
