/**
 * URL helpers for active-state detection in the navigation rail.
 * Mirrors the request()->routeIs(...) checks of the Blade navigation
 * without Ziggy (decision D2).
 */

/** Strip query string and trailing slash from an Inertia page URL. */
export function normalizePath(url: string): string {
    const path = url.split('?')[0] ?? url;

    return path.length > 1 && path.endsWith('/') ? path.slice(0, -1) : path;
}

/**
 * Whether the current Inertia URL matches a route path.
 * Exact mode matches the dashboard route; prefix mode mirrors wildcard
 * routeIs checks (e.g. 'deals.*', 'admin.*').
 */
export function isUrlActive(url: string, path: string, exact = false): boolean {
    const current = normalizePath(url);

    return exact ? current === path : current === path || current.startsWith(`${path}/`);
}
