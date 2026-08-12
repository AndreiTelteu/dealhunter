import type { ReactNode } from 'react';
import { Head } from '@inertiajs/react';
import Navigation from '../components/Navigation';
import { FlashMessages } from '../components/ui/Flash';

interface AppLayoutProps {
    /** Browser tab title; rendered as "<title> - <appName>". */
    title?: string;
    /** Equivalent of the Blade $header slot. */
    header?: ReactNode;
    children: ReactNode;
}

/**
 * Port of layouts/app.blade.php — instrument rail navigation, optional
 * page-heading header, flash emission lines from shared props, and the
 * engraved footer placard.
 */
export default function AppLayout({ title, header, children }: AppLayoutProps) {
    const year = new Date().getFullYear();

    return (
        <div className="flex min-h-screen flex-col">
            <Head title={title} />
            <Navigation />

            {header && (
                <header className="border-b border-hairline bg-bench">
                    <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">{header}</div>
                </header>
            )}

            <FlashMessages className="mx-auto max-w-7xl px-4 pt-4 sm:px-6 lg:px-8" />

            <main className="flex-1">{children}</main>

            <footer className="border-t border-hairline">
                <div className="mx-auto max-w-7xl px-4 py-5 sm:px-6 lg:px-8">
                    <p className="font-mono text-[0.65rem] uppercase text-dim/50">
                        OLX·Deal Hunter &mdash; camera de analiză &middot; &copy; {year}
                    </p>
                </div>
            </footer>
        </div>
    );
}
