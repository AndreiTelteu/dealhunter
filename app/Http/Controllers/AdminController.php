<?php

namespace App\Http\Controllers;

use App\Jobs\RunDealCrawl;
use App\Models\CrawlLog;
use App\Models\Deal;
use App\Models\HuntedDeal;
use App\Models\SystemHealth;
use App\Services\CrawlLogService;
use App\Services\SystemHealthService;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Inertia\Inertia;
use Inertia\Response;

class AdminController extends Controller
{
    public function __construct(
        private CrawlLogService $crawlLogService,
        private SystemHealthService $healthService,
        Request $request,
    ) {
        abort_unless(
            Schema::hasColumn('users', 'is_admin') && (bool) $request->user()?->getAttribute('is_admin'),
            403,
        );
    }

    /**
     * Show admin dashboard
     */
    public function dashboard(): Response
    {
        // Get recent crawl statistics
        $crawlStats = $this->crawlLogService->getRecentStats(24);

        // Get system health
        $overallHealth = $this->healthService->getOverallHealth();

        // Get recent crawl logs
        $recentCrawls = CrawlLog::with('user')
            ->recent(24)
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Get active hunted deals count
        $activeHuntedDeals = HuntedDeal::where('is_active', true)->count();
        $totalDeals = Deal::count();

        return Inertia::render('Admin/Dashboard', [
            'crawlStats' => [
                'totalCrawls' => (int) $crawlStats['total_crawls'],
                'successfulCrawls' => (int) $crawlStats['successful_crawls'],
                'failedCrawls' => (int) $crawlStats['failed_crawls'],
                'partialSuccessCrawls' => (int) $crawlStats['partial_success_crawls'],
                'totalListingsFound' => (int) $crawlStats['total_listings_found'],
                'totalDealsCreated' => (int) $crawlStats['total_deals_created'],
                'totalDealsUpdated' => (int) $crawlStats['total_deals_updated'],
                'totalSnapshotsCreated' => (int) $crawlStats['total_snapshots_created'],
                'averageDurationMs' => $crawlStats['average_duration_ms'] !== null
                    ? (float) $crawlStats['average_duration_ms']
                    : null,
                'averageSuccessRate' => $crawlStats['average_success_rate'] !== null
                    ? (float) $crawlStats['average_success_rate']
                    : null,
                'lastCrawlAt' => $crawlStats['last_crawl_at']?->toIso8601String(),
            ],
            'systemHealth' => [
                'overallStatus' => $overallHealth['overall_status'],
                'summary' => $overallHealth['summary'],
                'lastCheckLabel' => $overallHealth['last_check']?->diffForHumans(),
            ],
            'systemComponents' => collect($overallHealth['components'])
                ->map(fn (?SystemHealth $health) => $health === null ? null : [
                    'name' => $health->component,
                    'status' => $health->status,
                    'message' => $health->message,
                    'responseTimeMs' => $health->response_time_ms,
                ])
                ->all(),
            'recentCrawls' => $recentCrawls
                ->map(fn (CrawlLog $log) => $this->serializeRecentCrawl($log))
                ->all(),
            'activeHuntedDeals' => $activeHuntedDeals,
            'totalDeals' => $totalDeals,
            'crawlerConfig' => [
                'maxPagesPerSearch' => config('crawler.max_pages_per_search', 3),
                'requestDelayMs' => config('crawler.request_delay_ms', 2000),
                'maxListingsPerRun' => config('crawler.max_listings_per_run', 100),
                'mcpEndpoint' => config('crawler.mcp_playwright_endpoint'),
                'aiClassificationEnabled' => config('features.ai_classification_enabled', true),
            ],
            'huntedDealsOptions' => HuntedDeal::where('is_active', true)
                ->with('user')
                ->get()
                ->map(fn (HuntedDeal $huntedDeal) => [
                    'id' => $huntedDeal->id,
                    'label' => $huntedDeal->search_term.' ('.($huntedDeal->user?->name ?? 'necunoscut').')',
                ])
                ->all(),
            'links' => [
                'systemHealth' => route('admin.system-health'),
                'crawlLogs' => route('admin.crawl-logs'),
                'configuration' => route('admin.configuration'),
                'runHealthCheck' => route('admin.run-health-check'),
                'triggerCrawl' => route('admin.trigger-crawl'),
            ],
        ]);
    }

    /**
     * Show crawl logs with filtering
     */
    public function crawlLogs(Request $request): Response
    {
        $query = CrawlLog::with('user')->orderBy('started_at', 'desc');

        // Apply filters
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        if ($request->filled('date_from')) {
            $query->where('started_at', '>=', Carbon::parse($request->date_from));
        }

        if ($request->filled('date_to')) {
            $query->where('started_at', '<=', Carbon::parse($request->date_to)->endOfDay());
        }

        $logs = $query->paginate(20)->withQueryString();

        return Inertia::render('Admin/CrawlLogs', [
            'logs' => [
                'data' => $logs->through(fn (CrawlLog $log) => $this->serializeCrawlLogList($log))->all(),
                'links' => $logs->linkCollection()
                    ->map(fn (array $link) => [
                        'url' => $link['url'],
                        'label' => $link['label'],
                        'active' => (bool) $link['active'],
                    ])
                    ->all(),
                'meta' => [
                    'currentPage' => $logs->currentPage(),
                    'lastPage' => $logs->lastPage(),
                    'perPage' => $logs->perPage(),
                    'total' => $logs->total(),
                    'from' => $logs->firstItem(),
                    'to' => $logs->lastItem(),
                ],
            ],
            'filters' => [
                'status' => $request->get('status'),
                'type' => $request->get('type'),
                'dateFrom' => $request->get('date_from'),
                'dateTo' => $request->get('date_to'),
                'hasActiveFilters' => $request->hasAny(['status', 'type', 'date_from', 'date_to']),
            ],
            'links' => [
                'index' => route('admin.crawl-logs'),
                'dashboard' => route('admin.dashboard'),
            ],
        ]);
    }

    /**
     * Show system health monitoring
     */
    public function systemHealth(): Response
    {
        // Run fresh health checks
        $healthResults = $this->healthService->checkAllComponents();

        // Get overall health status
        $overallHealth = $this->healthService->getOverallHealth();

        // Get recent health history for charts
        $healthHistory = SystemHealth::recent(24 * 60) // Last 24 hours
            ->orderBy('checked_at', 'desc')
            ->get()
            ->groupBy('component');

        return Inertia::render('Admin/SystemHealth', [
            'overallHealth' => [
                'overallStatus' => $overallHealth['overall_status'],
                'summary' => $overallHealth['summary'],
                'lastCheckLabel' => $overallHealth['last_check']?->diffForHumans(),
            ],
            'healthResults' => collect($healthResults)
                ->map(fn (SystemHealth $health) => $this->serializeHealthCheck($health))
                ->values()
                ->all(),
            'healthHistory' => $healthHistory
                ->filter(fn ($checks) => $checks->count() > 1)
                ->map(fn ($checks, string $component) => $this->serializeHealthHistory($component, $checks))
                ->values()
                ->all(),
            'links' => [
                'dashboard' => route('admin.dashboard'),
                'runHealthCheck' => route('admin.run-health-check'),
                'cleanupLogs' => route('admin.cleanup-logs'),
            ],
        ]);
    }

    /**
     * Show configuration management
     */
    public function configuration(): Response
    {
        return Inertia::render('Admin/Configuration', [
            'config' => [
                'crawler' => [
                    'maxPagesPerSearch' => config('crawler.max_pages_per_search', 3),
                    'requestDelayMs' => config('crawler.request_delay_ms', 2000),
                    'maxListingsPerRun' => config('crawler.max_listings_per_run', 100),
                    'mcpPlaywrightEndpoint' => config('crawler.mcp_playwright_endpoint'),
                    'userAgent' => config('crawler.user_agent'),
                ],
                'features' => [
                    'aiClassificationEnabled' => config('features.ai_classification_enabled', true),
                    'detailPageCrawling' => config('features.detail_page_crawling', false),
                    'imageUrlExtraction' => config('features.image_url_extraction', true),
                    'sellerInfoExtraction' => config('features.seller_info_extraction', true),
                ],
                'ai' => [
                    'provider' => config('ai.provider'),
                    'model' => config('ai.model'),
                    'confidenceThreshold' => config('ai.confidence_threshold', 0.7),
                ],
                'currency' => [
                    'defaultCurrency' => config('currency.default_currency', 'RON'),
                    'eurToRonRate' => config('currency.eur_to_ron_rate', 4.95),
                    'usdToRonRate' => config('currency.usd_to_ron_rate', 4.50),
                ],
            ],
            'links' => [
                'dashboard' => route('admin.dashboard'),
            ],
        ]);
    }

    /**
     * Trigger manual crawl
     */
    public function triggerCrawl(Request $request): RedirectResponse
    {
        $request->validate([
            'hunted_deal_id' => 'nullable|exists:hunted_deals,id',
            'dry_run' => 'boolean',
            'notes' => 'nullable|string|max:500',
        ]);

        try {
            RunDealCrawl::dispatch(
                huntedDealId: $request->integer('hunted_deal_id') ?: null,
                dryRun: $request->boolean('dry_run'),
                triggeredByUserId: $request->user()?->id,
            );

            return redirect()->back()->with('success', 'Manual crawl queued. Check crawl logs for results.');
        } catch (\Throwable $e) {
            Log::error('Manual crawl queueing failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->except(['_token']),
            ]);

            return redirect()->back()->with('error', 'Failed to queue manual crawl: '.$e->getMessage());
        }
    }

    /**
     * Run system health check
     */
    public function runHealthCheck(): RedirectResponse
    {
        try {
            $results = $this->healthService->checkAllComponents();

            $healthyCount = collect($results)->where('status', 'healthy')->count();
            $totalCount = count($results);

            if ($healthyCount === $totalCount) {
                return redirect()->back()->with('success', 'System health check completed. All components are healthy.');
            } else {
                $issues = collect($results)->where('status', '!=', 'healthy')->count();

                return redirect()->back()->with('warning', "System health check completed. Found {$issues} component(s) with issues.");
            }

        } catch (\Exception $e) {
            Log::error('Health check failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
            ]);

            return redirect()->back()->with('error', 'Health check failed: '.$e->getMessage());
        }
    }

    /**
     * Show crawl log details
     */
    public function showCrawlLog(CrawlLog $crawlLog): Response
    {
        $crawlLog->loadMissing('user');

        return Inertia::render('Admin/CrawlLogDetail', [
            'crawlLog' => $this->serializeCrawlLogDetail($crawlLog),
            'links' => [
                'crawlLogs' => route('admin.crawl-logs'),
            ],
        ]);
    }

    /**
     * Clean up old logs
     */
    public function cleanupLogs(Request $request): RedirectResponse
    {
        $request->validate([
            'crawl_logs_days' => 'required|integer|min:1|max:365',
            'health_logs_days' => 'required|integer|min:1|max:90',
        ]);

        try {
            $crawlLogsDeleted = $this->crawlLogService->cleanupOldLogs($request->crawl_logs_days);
            $healthLogsDeleted = $this->healthService->cleanupOldHealthChecks($request->health_logs_days);

            return redirect()->back()->with('success',
                "Cleanup completed. Deleted {$crawlLogsDeleted} crawl logs and {$healthLogsDeleted} health check records."
            );

        } catch (\Exception $e) {
            Log::error('Log cleanup failed', [
                'error' => $e->getMessage(),
                'user_id' => auth()->id(),
                'request_data' => $request->all(),
            ]);

            return redirect()->back()->with('error', 'Log cleanup failed: '.$e->getMessage());
        }
    }

    /**
     * Serialize a crawl log for the dashboard "recent crawls" strip.
     *
     * @return array<string, mixed>
     */
    private function serializeRecentCrawl(CrawlLog $log): array
    {
        return [
            'id' => $log->id,
            'typeLabel' => $this->crawlTypeLabel($log->type),
            'status' => $log->status,
            'totalErrors' => (int) $log->total_errors,
            'startedAtLabel' => $log->started_at->format('d M Y, H:i'),
            'totalListingsFound' => (int) $log->total_listings_found,
            'newDealsCreated' => (int) $log->new_deals_created,
            'triggeredByLabel' => ucfirst((string) $log->triggered_by),
            'userName' => $log->user?->name,
            'showUrl' => route('admin.crawl-logs.show', $log),
        ];
    }

    /**
     * Serialize a crawl log row for the crawl-logs ledger.
     *
     * @return array<string, mixed>
     */
    private function serializeCrawlLogList(CrawlLog $log): array
    {
        return [
            'id' => $log->id,
            'typeLabel' => $this->crawlTypeLabel($log->type),
            'status' => $log->status,
            'totalErrors' => (int) $log->total_errors,
            'startedAtDate' => $log->started_at->format('d M Y'),
            'startedAtTime' => $log->started_at->format('H:i:s'),
            'formattedDuration' => $log->formatted_duration,
            'huntedDealsProcessed' => (int) $log->hunted_deals_processed,
            'huntedDealsFailed' => (int) $log->hunted_deals_failed,
            'totalListingsFound' => (int) $log->total_listings_found,
            'listingsPerSecond' => $log->listings_per_second !== null ? (float) $log->listings_per_second : null,
            'newDealsCreated' => (int) $log->new_deals_created,
            'showUrl' => route('admin.crawl-logs.show', $log),
            'triggeredByLabel' => ucfirst((string) $log->triggered_by),
            'userName' => $log->user?->name,
        ];
    }

    /**
     * Serialize the full crawl log for the detail surface.
     *
     * @return array<string, mixed>
     */
    private function serializeCrawlLogDetail(CrawlLog $crawlLog): array
    {
        return [
            'id' => $crawlLog->id,
            'typeLabel' => $this->crawlTypeLabel($crawlLog->type),
            'status' => $crawlLog->status,
            'totalErrors' => (int) $crawlLog->total_errors,
            'startedAtLabel' => $crawlLog->started_at->format('d M Y, H:i:s'),
            'completedAtLabel' => $crawlLog->completed_at?->format('d M Y, H:i:s'),
            'formattedDuration' => $crawlLog->formatted_duration,
            'notes' => $crawlLog->notes,
            'huntedDealsProcessed' => (int) $crawlLog->hunted_deals_processed,
            'huntedDealsFailed' => (int) $crawlLog->hunted_deals_failed,
            'totalListingsFound' => (int) $crawlLog->total_listings_found,
            'listingsPerSecond' => $crawlLog->listings_per_second !== null ? (float) $crawlLog->listings_per_second : null,
            'newDealsCreated' => (int) $crawlLog->new_deals_created,
            'dealsUpdated' => (int) $crawlLog->deals_updated,
            'snapshotsCreated' => (int) $crawlLog->snapshots_created,
            'successRate' => $crawlLog->success_rate !== null ? (float) $crawlLog->success_rate : null,
            'averageMsPerSearch' => $crawlLog->duration_ms !== null && $crawlLog->hunted_deals_processed > 0
                ? (int) round($crawlLog->duration_ms / $crawlLog->hunted_deals_processed)
                : null,
            'errors' => $crawlLog->errors ?? [],
            'configuration' => collect($crawlLog->configuration ?? [])
                ->map(fn ($value, string $key) => [
                    'key' => $key,
                    'value' => $this->stringifyConfigValue($value),
                ])
                ->values()
                ->all(),
            'triggeredByLabel' => ucfirst((string) $crawlLog->triggered_by),
            'userName' => $crawlLog->user?->name,
        ];
    }

    /**
     * Serialize one health check result for the system-health surface.
     *
     * @return array<string, mixed>
     */
    private function serializeHealthCheck(SystemHealth $health): array
    {
        return [
            'name' => $health->component,
            'status' => $health->status,
            'message' => $health->message,
            'responseTimeMs' => $health->response_time_ms,
            'checkedAtLabel' => $health->checked_at->diffForHumans(),
            'details' => collect($health->details ?? [])
                ->map(fn ($value, string $key) => [
                    'key' => $key,
                    'value' => $this->stringifyConfigValue($value),
                ])
                ->values()
                ->all(),
        ];
    }

    /**
     * Serialize the response-time history bars for one component.
     *
     * @param  Collection<int, SystemHealth>  $checks
     * @return array<string, mixed>
     */
    private function serializeHealthHistory(string $component, $checks): array
    {
        $maxResponseTime = $checks->max('response_time_ms');

        return [
            'component' => $component,
            'label' => str_replace('_', ' ', $component),
            'bars' => $checks->take(20)
                ->reverse()
                ->filter(fn (SystemHealth $check) => $check->response_time_ms !== null)
                ->map(fn (SystemHealth $check) => $this->serializeHealthBar($check, $maxResponseTime))
                ->values()
                ->all(),
            'lastValueMs' => $checks->first()->response_time_ms,
        ];
    }

    /**
     * Serialize one response-time bar.
     *
     * @return array<string, mixed>
     */
    private function serializeHealthBar(SystemHealth $check, ?int $maxResponseTime): array
    {
        $height = $maxResponseTime > 0 ? ($check->response_time_ms / $maxResponseTime) * 100 : 0;

        return [
            'height' => round(max($height, 5), 1),
            'color' => match ($check->status) {
                'healthy' => '#7dffa8',
                'warning' => '#ffc46b',
                default => '#ff5d5d',
            },
            'title' => $check->checked_at->format('H:i').': '.$check->response_time_ms.'ms',
        ];
    }

    /**
     * Humanize a crawl type ("manual_crawl" => "Manual crawl").
     */
    private function crawlTypeLabel(string $type): string
    {
        return ucfirst(str_replace('_', ' ', $type));
    }

    /**
     * Stringify a scalar/array/null config value for read-only display.
     */
    private function stringifyConfigValue(mixed $value): string
    {
        return match (true) {
            is_bool($value) => $value ? 'Activată' : 'Dezactivată',
            is_array($value) => json_encode($value),
            $value === null || $value === '' => 'Neconfigurată',
            default => (string) $value,
        };
    }
}
