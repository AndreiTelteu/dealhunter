import type { ReactNode } from 'react';
import { Head, Link } from '@inertiajs/react';
import ApplicationLogo from '../components/ApplicationLogo';
import { routes } from '../routes';

interface GuestLayoutProps {
    /** Browser tab title; rendered as "<title> - <appName>". */
    title?: string;
    /** Destination label for the rail's back link (defaults to home). */
    backHref?: string;
    backLabel?: string;
    children: ReactNode;
}

/**
 * Port of layouts/guest.blade.php — slim instrument rail with the brand
 * placard, the access panel (parked beam at the entry edge), and the
 * footer placard.
 */
export default function GuestLayout({
    title,
    backHref = routes.welcome,
    backLabel = 'Prima pagină',
    children,
}: GuestLayoutProps) {
    const year = new Date().getFullYear();

    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title} />

            {/* Instrument rail */}
            <header className="border-b border-hairline bg-rail">
                <div className="mx-auto max-w-6xl px-5 sm:px-8">
                    <div className="flex h-16 items-center justify-between">
                        <Link
                            href={routes.welcome}
                            className="brand-mark focus-ring flex items-center rounded-sm"
                            aria-label="Deal Hunter - Prima pagină"
                        >
                            <ApplicationLogo />
                        </Link>
                        <Link
                            href={backHref}
                            className="placard focus-ring rounded-sm px-1 py-1 text-xs transition-colors hover:text-beam"
                        >
                            &larr; {backLabel}
                        </Link>
                    </div>
                </div>
            </header>

            {/* Access panel */}
            <main className="flex flex-1 flex-col items-center justify-center px-5 py-12 sm:px-8 sm:py-16">
                <div className="w-full sm:max-w-md">
                    <div className="relative overflow-hidden border border-hairline bg-bench px-6 py-8 sm:px-8 sm:py-10">
                        {/* parked beam at the panel's entry edge */}
                        <div className="beam-core beam-idle absolute bottom-0 left-0 top-0 w-[2px]" aria-hidden="true"></div>

                        <div className="relative">{children}</div>
                    </div>

                    <p className="mt-6 text-center font-mono text-[0.65rem] uppercase text-dim/50">
                        OLX·Deal Hunter &mdash; urmărim OLX-ul ca tu să nu o faci
                    </p>
                </div>
            </main>

            <footer className="border-t border-hairline bg-[#06080a]">
                <div className="mx-auto max-w-6xl px-5 py-6 sm:px-8">
                    <p className="text-center font-mono text-xs text-dim/50">&copy; {year} OLX·Deal Hunter</p>
                </div>
            </footer>
        </div>
    );
}
