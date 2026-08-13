/**
 * Static route path map (decision D2 — no Ziggy).
 *
 * Controllers pass fully-built URLs (via route()) through props for
 * parameterized routes. Only static paths live here.
 */
export const routes = {
    welcome: '/',
    login: '/login',
    register: '/register',
    logout: '/logout',
    dashboard: '/dashboard',
    huntedDealsIndex: '/hunted-deals',
    huntedDealsCreate: '/hunted-deals/create',
    dealsIndex: '/deals',
    aiClassificationIndex: '/ai-classification',
    adminDashboard: '/admin/dashboard',
    adminSystemHealth: '/admin/system-health',
    adminConfiguration: '/admin/configuration',
    adminCrawlLogs: '/admin/crawl-logs',
    profileEdit: '/profile',
    favoritesIndex: '/favorites',
} as const;

export type RoutePath = (typeof routes)[keyof typeof routes];
