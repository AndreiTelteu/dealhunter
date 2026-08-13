import { createInertiaApp } from '@inertiajs/react';
import type { SharedPageProps } from '@inertiajs/core';
import type { ComponentType } from 'react';
import { createRoot } from 'react-dom/client';

const appName = import.meta.env.VITE_APP_NAME ?? 'OLX Deal Hunter';

createInertiaApp({
    title: (title) => (title ? `${title} - ${appName}` : appName),
    resolve: (name) => {
        const pages = import.meta.glob('./pages/**/*.tsx', { eager: true }) as Record<
            string,
            { default: ComponentType<SharedPageProps> }
        >;
        const page = pages[`./pages/${name}.tsx`];

        if (!page) {
            throw new Error(`Inertia page module not found: ${name}`);
        }

        return page;
    },
    progress: {
        color: '#59e3ff',
        delay: 150,
        showSpinner: false,
    },
    setup({ el, App, props }) {
        if (el) {
            createRoot(el).render(<App {...props} />);
        }
    },
});
