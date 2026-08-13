import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface NavLinkProps {
    href: string;
    active?: boolean;
    children: ReactNode;
}

/**
 * Port of x-nav-link — a rail link on the instrument bar.
 * The caller computes `active` from the current Inertia URL
 * (mirrors request()->routeIs(...) in the Blade navigation).
 */
export default function NavLink({ href, active = false, children }: NavLinkProps) {
    return (
        <Link
            href={href}
            prefetch="hover"
            aria-current={active ? 'page' : undefined}
            className={`rail-link focus-ring inline-flex items-center border-b-2 px-1 pt-1 text-xs leading-5 transition duration-150 ease-in-out ${
                active ? 'rail-link-active' : 'border-transparent'
            }`}
        >
            {children}
        </Link>
    );
}
