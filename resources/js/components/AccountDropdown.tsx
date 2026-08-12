import { useEffect, useRef, useState } from 'react';
import { Link } from '@inertiajs/react';
import { routes } from '../routes';
import type { AuthUser } from '../types';

interface AccountDropdownProps {
    user: AuthUser;
}

/**
 * Port of the account dropdown in layouts/navigation.blade.php:
 * beam-status dot, user name, Profil link, and a POST logout action.
 * Keyboard accessible: Enter/Space opens, Escape closes and restores
 * focus to the trigger, clicks outside close the panel.
 */
export default function AccountDropdown({ user }: AccountDropdownProps) {
    const [open, setOpen] = useState(false);
    const containerRef = useRef<HTMLDivElement>(null);
    const triggerRef = useRef<HTMLButtonElement>(null);
    const panelRef = useRef<HTMLDivElement>(null);

    useEffect(() => {
        if (!open) {
            return;
        }

        const handlePointerDown = (event: MouseEvent): void => {
            if (!containerRef.current?.contains(event.target as Node)) {
                setOpen(false);
            }
        };

        const handleKeyDown = (event: KeyboardEvent): void => {
            if (event.key === 'Escape') {
                setOpen(false);
                triggerRef.current?.focus();
            }
        };

        document.addEventListener('mousedown', handlePointerDown);
        document.addEventListener('keydown', handleKeyDown);

        return () => {
            document.removeEventListener('mousedown', handlePointerDown);
            document.removeEventListener('keydown', handleKeyDown);
        };
    }, [open]);

    useEffect(() => {
        if (open) {
            panelRef.current?.querySelector<HTMLElement>('a, button')?.focus();
        }
    }, [open]);

    const itemClasses =
        'focus-ring block w-full px-4 py-2 text-start font-mono text-xs uppercase text-dim transition duration-150 ease-in-out hover:bg-[#0a0e11] hover:text-[#eaf4f6]';

    return (
        <div className="relative" ref={containerRef}>
            <button
                ref={triggerRef}
                type="button"
                onClick={() => setOpen((value) => !value)}
                aria-expanded={open}
                aria-haspopup="true"
                aria-label="Meniu cont"
                className="focus-ring inline-flex h-9 items-center gap-2 rounded-sm border border-hairline bg-bench px-2 font-mono text-xs text-dim transition duration-150 ease-in-out hover:border-[rgba(89,227,255,0.35)] hover:text-beam xl:px-3"
            >
                <span
                    className="inline-block h-1.5 w-1.5 rounded-full bg-[#7dffa8]"
                    style={{ boxShadow: '0 0 8px rgba(125,255,168,0.6)' }}
                    aria-hidden="true"
                ></span>
                <span
                    className="grid h-5 w-5 place-items-center border border-hairline bg-rail text-[0.65rem] text-[#eaf4f6] xl:hidden"
                    aria-hidden="true"
                >
                    {user.name.charAt(0)}
                </span>
                <span className="hidden max-w-[12rem] truncate xl:inline">{user.name}</span>
                <svg className="h-3.5 w-3.5 fill-current" viewBox="0 0 20 20" aria-hidden="true">
                    <path
                        fillRule="evenodd"
                        d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z"
                        clipRule="evenodd"
                    />
                </svg>
            </button>

            {open && (
                <div
                    ref={panelRef}
                    role="menu"
                    className="absolute end-0 z-50 mt-2 w-48 origin-top-right rounded-sm border border-hairline bg-bench py-1 shadow-[0_10px_24px_-8px_rgba(0,0,0,0.85)]"
                >
                    <Link href={routes.profileEdit} role="menuitem" className={itemClasses}>
                        Profil
                    </Link>
                    <Link
                        href={routes.logout}
                        method="post"
                        as="button"
                        type="button"
                        role="menuitem"
                        className={itemClasses}
                    >
                        Deconectare
                    </Link>
                </div>
            )}
        </div>
    );
}
