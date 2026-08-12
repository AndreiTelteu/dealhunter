/**
 * Domain DTO types — they mirror the explicit server-side serialization the
 * controllers perform (e.g. DashboardController::serializeDeal). Controllers
 * build these arrays; nothing raw/Eloquent ever reaches the frontend.
 *
 * Fields only exist here when a controller serializes them or a model/Blade
 * surface actually uses them — no speculative columns.
 */

/* ------------------------------------------------------------------ */
/* Deals                                                               */
/* ------------------------------------------------------------------ */

export interface DealMedia {
    /** Local storage URL only — remote URLs are never exposed. */
    url: string;
}

export interface Deal {
    id: number;
    title: string;
    matchesIntent: boolean;
    /** Serialized by DealController surfaces; used for the "Potrivit NN%" badge. */
    intentScore?: number | null;
    likelyWorking: boolean;
    /** Short description excerpt, serialized on list surfaces only. */
    description?: string | null;
    /** Server-computed "new within 24h" flag (created_at >= now - 1 day). */
    isNew?: boolean;
    priceAmount: number | null;
    priceCurrency: string | null;
    location: string | null;
    createdAt: string;
    /** Human-diff of last_seen_at, serialized on list surfaces. */
    lastSeenAt?: string | null;
    /** Snapshot count, serialized on the deals index surface. */
    snapshotsCount?: number;
    searchTerm: string | null;
    /** URL of the owning hunted deal (search-term link target). */
    huntedDealUrl?: string;
    isFavorite: boolean;
    media: DealMedia[];
    showUrl: string;
    externalUrl: string;
    toggleFavoriteUrl: string;
}

/** One price/ledger snapshot row (deals.show history). */
export interface DealSnapshot {
    id: number;
    title: string | null;
    priceAmount: number | null;
    priceCurrency: string | null;
    location: string | null;
    sellerName: string | null;
    capturedAt: string;
}

/** Filter facet counts (deals index + hunted-deal show). */
export interface DealFilterCounts {
    total: number;
    newItems: number;
    matchesIntent: number;
    likelyWorking: number;
    priceDrops: number;
}

/** Crawl statistics block on the hunted-deal show page. */
export interface HuntedDealStats {
    totalDeals: number;
    newDeals24h: number;
    matchingIntent: number;
    likelyWorking: number;
    priceDrops: number;
}

/* ------------------------------------------------------------------ */
/* Hunted deals                                                        */
/* ------------------------------------------------------------------ */

export interface HuntedDealPriceSnapshot {
    id: number;
    averagePrice: number | null;
    minPrice: number | null;
    maxPrice: number | null;
    dealsCount: number;
    priceCurrency: string | null;
    capturedAt: string;
}

export interface HuntedDeal {
    id: number;
    searchTerm: string;
    isActive: boolean;
    notes: string | null;
    dealsCount: number;
    lastCrawledAt: string | null;
    showUrl: string;
    editUrl: string;
    /** Present on the detail/edit surfaces, absent in list summaries. */
    excludedPhrases?: string[];
    preferredPhrases?: string[];
    createdAt?: string;
    updatedAt?: string;
    latestPriceSnapshot?: HuntedDealPriceSnapshot | null;
}

/* ------------------------------------------------------------------ */
/* Favorites                                                           */
/* ------------------------------------------------------------------ */

export interface Favorite {
    id: number;
    createdAt: string;
    deal: Deal;
}

/** JSON shape returned by the favorite toggle endpoint. */
export interface FavoriteToggleResponse {
    favorited: boolean;
    count: number;
}

/* ------------------------------------------------------------------ */
/* Admin surfaces                                                      */
/* ------------------------------------------------------------------ */

export interface CrawlLog {
    id: number;
    type: string;
    status: string;
    startedAt: string;
    completedAt: string | null;
    durationMs: number | null;
    formattedDuration: string;
    huntedDealsProcessed: number;
    huntedDealsFailed: number;
    totalListingsFound: number;
    newDealsCreated: number;
    dealsUpdated: number;
    snapshotsCreated: number;
    totalErrors: number;
    successRate: number | null;
    listingsPerSecond: number | null;
    triggeredBy: string;
    userName: string | null;
    showUrl: string;
}

export interface SystemHealthCheck {
    component: string;
    status: 'healthy' | 'warning' | 'critical' | 'unknown';
    message: string | null;
    responseTimeMs: number | null;
    checkedAt: string;
}

/* ------------------------------------------------------------------ */
/* AI classification                                                   */
/* ------------------------------------------------------------------ */

export interface AiConnectionTest {
    success: boolean;
    error?: string;
    provider?: string;
    model?: string;
    response?: string;
}

export interface AiClassificationConfig {
    availableModels: string[];
    connectionTest: AiConnectionTest;
    currentProvider: string | null;
    currentModel: string | null;
    aiEnabled: boolean;
}
