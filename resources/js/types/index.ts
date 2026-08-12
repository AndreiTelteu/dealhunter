/**
 * Shared Inertia page-prop types (mirrors HandleInertiaRequests::share()).
 * Secrets and raw models are never shared — keep this surface explicit.
 */

export interface AuthUser {
    id: number;
    name: string;
    email: string;
    isAdmin: boolean;
}

export interface FlashMessages {
    success: string | null;
    error: string | null;
    info: string | null;
    warning: string | null;
}

export interface SharedPageProps {
    appName: string;
    auth: {
        user: AuthUser | null;
    };
    flash: FlashMessages;
    favoritesCount: number;
    /** Allow extra page-level props without losing the PageProps contract. */
    [key: string]: unknown;
}

export * from './domain';
export * from './pagination';
