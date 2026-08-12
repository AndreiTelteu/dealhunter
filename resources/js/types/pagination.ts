/**
 * Laravel paginator wire types.
 *
 * Controllers serialize `$paginator->linkCollection()` (url/label/active
 * rows) plus the meta subset below — camelCase, matching the explicit
 * DTO mapping convention. `withQueryString()` means every link URL already
 * carries the current filter/sort query string, so Inertia visits preserve
 * state without extra work.
 */

export interface PaginationLink {
    /** Null for disabled rows (previous on page 1, gaps, next on last page). */
    url: string | null;
    /** May contain HTML entities (&laquo;, &raquo;, &hellip;). */
    label: string;
    active: boolean;
}

export interface PaginationMeta {
    currentPage: number;
    lastPage: number;
    perPage: number;
    total: number;
    from: number | null;
    to: number | null;
}

/** A paginated collection handed to an Inertia page. */
export interface Paginated<T> {
    data: T[];
    links: PaginationLink[];
    meta: PaginationMeta;
}
