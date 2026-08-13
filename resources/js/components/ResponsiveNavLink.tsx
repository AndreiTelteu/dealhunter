import type { ReactNode } from 'react';
import { Link } from '@inertiajs/react';

interface ResponsiveNavLinkProps {
    href: string;
    active?: boolean;
    method?: 'get' | 'post';
    children: ReactNode;
}

/**
 * Port of x-responsive-nav-link — full-width beam-edge link used in the
 * mobile navigation panel. POST support covers the logout action.
 */
export default function ResponsiveNavLink({
    href,
    active = false,
    method = 'get',
    children,
}: ResponsiveNavLinkProps) {
    return (
        <Link
            href={href}
            method={method}
            prefetch={method === 'get' ? 'hover' : undefined}
            as="button"
            type="button"
            aria-current={active ? 'page' : undefined}
            className={`focus-ring block w-full border-l-2 py-2 pe-4 ps-3 text-start font-mono text-xs uppercase transition duration-150 ease-in-out ${
                active
                    ? 'border-[#59e3ff] bg-[rgba(89,227,255,0.06)] text-[#59e3ff]'
                    : 'border-transparent text-dim hover:border-[#1c242a] hover:bg-[#0a0e11] hover:text-[#eaf4f6]'
            }`}
        >
            {children}
        </Link>
    );
}
