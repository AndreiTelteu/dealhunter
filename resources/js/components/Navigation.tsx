import { useEffect, useId, useRef, useState } from 'react';
import { Link, usePage } from '@inertiajs/react';
import ApplicationLogo from './ApplicationLogo';
import AccountDropdown from './AccountDropdown';
import FavoritesBadge from './FavoritesBadge';
import NavLink from './NavLink';
import ResponsiveNavLink from './ResponsiveNavLink';
import { isUrlActive } from '../lib/navigation';
import { routes } from '../routes';
import type { SharedPageProps } from '../types';

/**
 * Port of layouts/navigation.blade.php — the instrument rail with the
 * brand placard, primary nav links, favorites badge, account dropdown,
 * and the responsive mobile panel. Admin links render only when the
 * shared auth user has the isAdmin capability (authorization-aware).
 */
export default function Navigation() {
    const { url, props } = usePage<SharedPageProps>();
    const user = props.auth.user;
    const [open, setOpen] = useState(false);
    const menuId = useId();
    const toggleRef = useRef<HTMLButtonElement>(null);

    useEffect(() => {
        setOpen(false);
    }, [url]);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handleKeyDown = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                setOpen(false);
                toggleRef.current?.focus();
            }
        };

        document.addEventListener('keydown', handleKeyDown);

        return () => document.removeEventListener('keydown', handleKeyDown);
    }, [open]);

    const primaryLinks = [
        { label: 'Panou', href: routes.dashboard, active: isUrlActive(url, routes.dashboard, true) },
        {
            label: 'Urmărite',
            href: routes.huntedDealsIndex,
            active: isUrlActive(url, routes.huntedDealsIndex),
        },
        { label: 'Anunțuri', href: routes.dealsIndex, active: isUrlActive(url, routes.dealsIndex) },
        {
            label: 'Testare AI',
            href: routes.aiClassificationIndex,
            active: isUrlActive(url, routes.aiClassificationIndex),
        },
        ...(user?.isAdmin
            ? [
                  {
                      label: 'Admin',
                      href: routes.adminDashboard,
                      active: isUrlActive(url, routes.adminDashboard.split('/').slice(0, 2).join('/')),
                  },
              ]
            : []),
    ];

    return (
        <nav className="border-b border-hairline bg-rail">
            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <div className="flex h-16 items-center justify-between gap-3">
                    {/* Wordmark */}
                    <div className="relative z-10 flex shrink-0 items-center">
                        <Link
                            href={routes.dashboard}
                            className="brand-mark focus-ring flex shrink-0 items-center rounded-sm"
                            aria-label="Deal Hunter - Panou"
                        >
                            <ApplicationLogo />
                        </Link>
                    </div>

                    {/* Primary navigation links */}
                    <div className="hidden min-w-0 flex-1 items-stretch justify-center gap-4 sm:-my-px sm:flex lg:gap-7">
                        {primaryLinks.map((link) => (
                            <NavLink key={link.href} href={link.href} active={link.active}>
                                {link.label}
                            </NavLink>
                        ))}
                    </div>

                    {/* Favorites badge (desktop) */}
                    <div className="hidden shrink-0 sm:flex sm:items-center">
                        <FavoritesBadge count={props.favoritesCount} />
                    </div>

                    {/* Account dropdown (desktop) */}
                    {user && (
                        <div className="hidden shrink-0 sm:flex sm:items-center">
                            <AccountDropdown user={user} />
                        </div>
                    )}

                    {/* Favorites badge (mobile) */}
                    <div className="flex shrink-0 items-center sm:hidden">
                        <FavoritesBadge count={props.favoritesCount} compact />
                    </div>

                    {/* Hamburger */}
                    <div className="-me-2 flex shrink-0 items-center sm:hidden">
                        <button
                            ref={toggleRef}
                            type="button"
                            onClick={() => setOpen((value) => !value)}
                            aria-expanded={open}
                            aria-controls={menuId}
                            aria-label="Meniu"
                            className="focus-ring inline-flex items-center justify-center rounded-sm border border-hairline p-2 text-dim transition duration-150 ease-in-out hover:border-[rgba(89,227,255,0.35)] hover:text-beam"
                        >
                            <svg className="h-5 w-5" stroke="currentColor" fill="none" viewBox="0 0 24 24" aria-hidden="true">
                                <path
                                    className={open ? 'hidden' : 'inline-flex'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="1.5"
                                    d="M4 6h16M4 12h16M4 18h16"
                                />
                                <path
                                    className={open ? 'inline-flex' : 'hidden'}
                                    strokeLinecap="round"
                                    strokeLinejoin="round"
                                    strokeWidth="1.5"
                                    d="M6 18L18 6M6 6l12 12"
                                />
                            </svg>
                        </button>
                    </div>
                </div>
            </div>

            {/* Responsive navigation panel */}
            <div
                id={menuId}
                className={`border-t border-hairline bg-[#06080a] sm:hidden ${open ? 'block' : 'hidden'}`}
            >
                <div className="space-y-1 pb-3 pt-2">
                    {primaryLinks.map((link) => (
                        <ResponsiveNavLink key={link.href} href={link.href} active={link.active}>
                            {link.label}
                        </ResponsiveNavLink>
                    ))}
                </div>

                {user && (
                    <div className="border-t border-hairline pb-2 pt-4">
                        <div className="flex items-center gap-2.5 px-4">
                            <span
                                className="inline-block h-1.5 w-1.5 rounded-full bg-[#7dffa8]"
                                style={{ boxShadow: '0 0 8px rgba(125,255,168,0.6)' }}
                                aria-hidden="true"
                            ></span>
                            <div>
                                <div className="text-sm font-medium text-[#eaf4f6]">{user.name}</div>
                                <div className="font-mono text-xs text-dim">{user.email}</div>
                            </div>
                        </div>

                        <div className="mt-3 space-y-1">
                            <ResponsiveNavLink href={routes.profileEdit}>Profil</ResponsiveNavLink>
                            <ResponsiveNavLink href={routes.logout} method="post">
                                Deconectare
                            </ResponsiveNavLink>
                        </div>
                    </div>
                )}
            </div>
        </nav>
    );
}
