<?php

namespace App\Http\Controllers;

use App\Jobs\RunDealCrawl;
use App\Models\CrawlLog;
use App\Models\HuntedDeal;
use App\Models\SystemHealth;
use App\Services\CrawlLogService;
use App\Services\SystemHealthService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

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
    public function dashboard(): View
    {
        // Get recent crawl statistics
        $crawlStats = $this->crawlLogService->getRecentStats(24);

        // Get system health
        $systemHealth = $this->healthService->getOverallHealth();

        // Get recent crawl logs
        $recentCrawls = CrawlLog::with('user')
            ->recent(24)
            ->orderBy('started_at', 'desc')
            ->limit(10)
            ->get();

        // Get active hunted deals count
        $activeHuntedDeals = HuntedDeal::where('is_active', true)->count();
        $totalDeals = \App\Models\Deal::count();

        // Get crawler configuration
        $crawlerConfig = [
            'max_pages_per_search' => config('crawler.max_pages_per_search', 3),
            'request_delay_ms' => config('crawler.request_delay_ms', 2000),
            'max_listings_per_run' => config('crawler.max_listings_per_run', 100),
            'mcp_endpoint' => config('crawler.mcp_playwright_endpoint'),
            'ai_classification_enabled' => config('features.ai_classification_enabled', true),
        ];

        return view('admin.dashboard', compact(
            'crawlStats',
            'systemHealth',
            'recentCrawls',
            'activeHuntedDeals',
            'totalDeals',
            'crawlerConfig'
        ));
    }

    /**
     * Show crawl logs with filtering
     */
    public function crawlLogs(Request $request): View
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

        return view('admin.crawl-logs', compact('logs'));
    }

    /**
     * Show system health monitoring
     */
    public function systemHealth(): View
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

        return view('admin.system-health', compact(
            'healthResults',
            'overallHealth',
            'healthHistory'
        ));
    }

    /**
     * Show configuration management
     */
    public function configuration(): View
    {
        $config = [
            'crawler' => [
                'max_pages_per_search' => config('crawler.max_pages_per_search', 3),
                'request_delay_ms' => config('crawler.request_delay_ms', 2000),
                'max_listings_per_run' => config('crawler.max_listings_per_run', 100),
                'mcp_playwright_endpoint' => config('crawler.mcp_playwright_endpoint'),
                'user_agent' => config('crawler.user_agent'),
            ],
            'features' => [
                'ai_classification_enabled' => config('features.ai_classification_enabled', true),
                'detail_page_crawling' => config('features.detail_page_crawling', false),
                'image_url_extraction' => config('features.image_url_extraction', true),
                'seller_info_extraction' => config('features.seller_info_extraction', true),
            ],
            'ai' => [
                'provider' => config('ai.provider'),
                'model' => config('ai.model'),
                'confidence_threshold' => config('ai.confidence_threshold', 0.7),
            ],
            'currency' => [
                'default_currency' => config('currency.default_currency', 'RON'),
                'eur_to_ron_rate' => config('currency.eur_to_ron_rate', 4.95),
                'usd_to_ron_rate' => config('currency.usd_to_ron_rate', 4.50),
            ],
        ];

        return view('admin.configuration', compact('config'));
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
    public function showCrawlLog(CrawlLog $crawlLog): View
    {
        return view('admin.crawl-log-detail', compact('crawlLog'));
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
}
