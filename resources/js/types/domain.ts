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
    /** Human-diff of created_at; dashboard serializes it, deals/favorites omit it. */
    createdAt?: string;
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
    /** ISO-8601 timestamp, used by the client to build the price trace. */
    capturedAt: string;
    /** Server-formatted "d M Y, H:i" label for the ledger rows. */
    capturedAtLabel: string;
}

/**
 * "Citire curentă" on the deal detail surface — the latest snapshot when
 * one exists, otherwise the deal itself (the Blade merge, server-side).
 * Classification fields stay nullable so the "Fără clasificare" state
 * survives.
 */
export interface DealCurrentReadout {
    title: string;
    priceAmount: number | null;
    priceCurrency: string | null;
    priceRaw: string | null;
    description: string | null;
    matchesIntent: boolean | null;
    intentScore: number | null;
    likelyWorking: boolean | null;
    confidence: number | null;
    snapshotCapturedAtLabel: string | null;
    postedAtLabel: string | null;
    location: string | null;
    sellerName: string | null;
    sellerUrl: string | null;
}

/** The deal itself on the deal detail surface (identity + gallery). */
export interface DealDetail {
    id: number;
    title: string;
    createdAtLabel: string;
    lastSeenAtLabel: string | null;
    externalUrl: string | null;
    isFavorite: boolean;
    /** Local-only media, server-filtered — no remote fallback, ever. */
    media: DealMedia[];
    toggleFavoriteUrl: string;
}

/** Owning hunted deal summary on the deal detail surface. */
export interface HuntedDealSummary {
    searchTerm: string;
    isActive: boolean;
    showUrl: string;
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

/** Full show-surface summary for a hunted deal (metadata block + header). */
export interface HuntedDealShowSummary {
    id: number;
    searchTerm: string;
    isActive: boolean;
    notes: string | null;
    /** Human-diff of last_crawled_at for the header, or null when never crawled. */
    lastCrawledAt: string | null;
    createdAt: string;
    createdAtTime: string;
    updatedAt: string;
    updatedAtTime: string;
    lastCrawledAtDate: string | null;
    lastCrawledAtTime: string | null;
    showUrl: string;
    editUrl: string;
}

/** One aggregate price reading for the hunted-deal spectrum trace. */
export interface HuntedDealChartSample {
    min: number;
    average: number;
    max: number;
    currency: string;
    count: number;
    /** Server-formatted "d M Y, H:i" label for the hover popup. */
    captured: string;
    /** Unix timestamp, used by the client to place the sample on the time axis. */
    timestamp: number;
}

/**
 * Price-spectrum trace for the hunted-deal show surface. `hasTrace` is true
 * once two or more snapshots exist; a single snapshot is surfaced through
 * `latestSnapshot`, and zero snapshots leaves both empty.
 */
export interface HuntedDealChart {
    hasTrace: boolean;
    currency: string;
    sampleCount: number;
    firstCaptured: string | null;
    lastCaptured: string | null;
    samples: HuntedDealChartSample[];
    latestSnapshot: {
        minPrice: number;
        averagePrice: number;
        maxPrice: number;
        priceCurrency: string;
        capturedAt: string;
        dealsCount: number;
    } | null;
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

export type CrawlLogStatus = 'started' | 'completed' | 'failed' | 'partial';

export type HealthStatus = 'healthy' | 'warning' | 'critical' | 'unknown';

/** Recent crawl statistics block (dashboard). */
export interface CrawlStats {
    totalCrawls: number;
    successfulCrawls: number;
    failedCrawls: number;
    partialSuccessCrawls: number;
    totalListingsFound: number;
    totalDealsCreated: number;
    totalDealsUpdated: number;
    totalSnapshotsCreated: number;
    averageDurationMs: number | null;
    averageSuccessRate: number | null;
    /** ISO-8601 of the most recent crawl start, or null when none. */
    lastCrawlAt: string | null;
}

/** Per-status counts across the monitored components. */
export interface HealthSummary {
    healthy: number;
    warning: number;
    critical: number;
    unknown: number;
    total: number;
}

/** Overall system health readout (dashboard + system-health). */
export interface SystemHealthOverview {
    overallStatus: HealthStatus;
    summary: HealthSummary;
    /** Human-diff of the latest check across components, or null. */
    lastCheckLabel: string | null;
}

/** One component readout in the dashboard alignment strip. */
export interface SystemComponentHealth {
    name: string;
    status: HealthStatus;
    message: string | null;
    responseTimeMs: number | null;
}

/** A compact crawl row in the dashboard "recent crawls" strip. */
export interface RecentCrawl {
    id: number;
    typeLabel: string;
    status: CrawlLogStatus;
    totalErrors: number;
    startedAtLabel: string;
    totalListingsFound: number;
    newDealsCreated: number;
    triggeredByLabel: string;
    userName: string | null;
    showUrl: string;
}

/** Crawler parameters readout (dashboard). */
export interface CrawlerConfig {
    maxPagesPerSearch: number | string;
    requestDelayMs: number | string;
    maxListingsPerRun: number | string;
    mcpEndpoint: string | null;
    aiClassificationEnabled: boolean;
}

/** Manual-crawl select option (dashboard). */
export interface HuntedDealOption {
    id: number;
    label: string;
}

/** A crawl row in the crawl-logs ledger. */
export interface CrawlLog {
    id: number;
    typeLabel: string;
    status: CrawlLogStatus;
    totalErrors: number;
    startedAtDate: string;
    startedAtTime: string;
    formattedDuration: string;
    huntedDealsProcessed: number;
    huntedDealsFailed: number;
    totalListingsFound: number;
    listingsPerSecond: number | null;
    newDealsCreated: number;
    showUrl: string;
    triggeredByLabel: string;
    userName: string | null;
}

/** Filter state echoed back for the crawl-logs surface. */
export interface CrawlLogsFilters {
    status: string | null;
    type: string | null;
    dateFrom: string | null;
    dateTo: string | null;
    hasActiveFilters: boolean;
}

/** One key/value readout of the crawl-log stored configuration. */
export interface CrawlLogConfigurationEntry {
    key: string;
    value: string;
}

/** Full serialized crawl log for the detail surface. */
export interface CrawlLogDetail {
    id: number;
    typeLabel: string;
    status: CrawlLogStatus;
    totalErrors: number;
    startedAtLabel: string;
    completedAtLabel: string | null;
    formattedDuration: string;
    notes: string | null;
    huntedDealsProcessed: number;
    huntedDealsFailed: number;
    totalListingsFound: number;
    listingsPerSecond: number | null;
    newDealsCreated: number;
    dealsUpdated: number;
    snapshotsCreated: number;
    successRate: number | null;
    /** Rounded duration/hunted_deals_processed, or null when not measurable. */
    averageMsPerSearch: number | null;
    errors: string[];
    configuration: CrawlLogConfigurationEntry[];
    triggeredByLabel: string;
    userName: string | null;
}

/** One key/value diagnostic detail of a health check. */
export interface HealthDetailEntry {
    key: string;
    value: string;
}

/** One component check on the system-health surface. */
export interface SystemHealthCheck {
    name: string;
    status: HealthStatus;
    message: string | null;
    responseTimeMs: number | null;
    checkedAtLabel: string;
    details: HealthDetailEntry[];
}

/** One response-time bar in the 24h history strip. */
export interface HealthHistoryBar {
    /** Percentage height (min 5). */
    height: number;
    /** Hex color keyed on status. */
    color: string;
    /** Hover title "H:i: NNms". */
    title: string;
}

/** Response-time history for a single component (only >1 check). */
export interface HealthHistoryComponent {
    component: string;
    label: string;
    bars: HealthHistoryBar[];
    lastValueMs: number | null;
}

/** A raw config scalar/boolean/null value on the configuration surface. */
export type AdminConfigValue = string | number | boolean | null;

/** Read-only configuration groups (exact key set — no secrets). */
export interface AdminConfig {
    crawler: {
        maxPagesPerSearch: AdminConfigValue;
        requestDelayMs: AdminConfigValue;
        maxListingsPerRun: AdminConfigValue;
        mcpPlaywrightEndpoint: AdminConfigValue;
        userAgent: AdminConfigValue;
    };
    features: {
        aiClassificationEnabled: AdminConfigValue;
        detailPageCrawling: AdminConfigValue;
        imageUrlExtraction: AdminConfigValue;
        sellerInfoExtraction: AdminConfigValue;
    };
    ai: {
        provider: AdminConfigValue;
        model: AdminConfigValue;
        confidenceThreshold: AdminConfigValue;
    };
    currency: {
        defaultCurrency: AdminConfigValue;
        eurToRonRate: AdminConfigValue;
        usdToRonRate: AdminConfigValue;
    };
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

/**
 * AJAX contract of POST /ai-classification/test (snake_case, unchanged from
 * the Blade-era endpoint). These are NOT Inertia props — the endpoint is a
 * plain JSON response for a non-navigation fetch().
 */
export interface AiClassificationAiResult {
    intent_score: number;
    matches_intent: boolean;
    likely_working: boolean | null;
    confidence: number;
    intent_confidence: number;
    working_confidence: number;
    reasoning: string;
}

export interface AiClassificationKeywordResult {
    matches_intent: boolean;
    intent_score: number | null;
    is_exact_match: boolean;
    likely_working: boolean | null;
    confidence: number;
    reasoning: string;
    is_high_confidence: boolean;
    working_condition_string: string;
}

export interface AiClassificationResult {
    success: boolean;
    ai_result?: AiClassificationAiResult;
    keyword_result?: AiClassificationKeywordResult;
    comparison?: {
        intent_match: boolean;
        working_condition_match: boolean;
    };
    error?: string;
    trace?: string | null;
}

/** AJAX contract of POST /ai-classification/test-connection. */
export interface AiConnectionTestResponse {
    success: boolean;
    result?: AiConnectionTest;
    error?: string;
}
