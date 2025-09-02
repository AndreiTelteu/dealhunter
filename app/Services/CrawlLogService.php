<?php

namespace App\Services;

use App\Models\CrawlLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

/**
 * Service for managing structured crawl logging
 */
class CrawlLogService
{
    /**
     * Start a new crawl log entry
     */
    public function startCrawl(
        string $type = 'crawl',
        string $triggeredBy = 'scheduler',
        ?User $user = null,
        ?string $notes = null
    ): CrawlLog {
        $crawlLog = CrawlLog::create([
            'type' => $type,
            'status' => 'started',
            'started_at' => Carbon::now(),
            'triggered_by' => $triggeredBy,
            'user_id' => $user?->id,
            'notes' => $notes,
            'configuration' => $this->getCurrentConfiguration(),
        ]);

        Log::channel('crawler')->info('Crawl started', [
            'crawl_log_id' => $crawlLog->id,
            'type' => $type,
            'triggered_by' => $triggeredBy,
            'user_id' => $user?->id,
        ]);

        return $crawlLog;
    }

    /**
     * Complete a crawl log with statistics
     */
    public function completeCrawl(CrawlLog $crawlLog, array $stats): void
    {
        $duration = $crawlLog->started_at->diffInMilliseconds(Carbon::now());
        
        $updateData = [
            'status' => $this->determineStatus($stats),
            'completed_at' => Carbon::now(),
            'duration_ms' => $duration,
            'hunted_deals_processed' => $stats['hunted_deals_processed'] ?? 0,
            'hunted_deals_failed' => $stats['hunted_deals_failed'] ?? 0,
            'total_listings_found' => $stats['total_listings_found'] ?? 0,
            'new_deals_created' => $stats['new_deals_created'] ?? 0,
            'deals_updated' => $stats['deals_updated'] ?? 0,
            'snapshots_created' => $stats['snapshots_created'] ?? 0,
            'total_errors' => $stats['total_errors'] ?? 0,
            'errors' => $stats['errors'] ?? [],
        ];

        // Calculate success rate
        if ($updateData['hunted_deals_processed'] > 0) {
            $successful = $updateData['hunted_deals_processed'] - $updateData['hunted_deals_failed'];
            $updateData['success_rate'] = round(($successful / $updateData['hunted_deals_processed']) * 100, 2);
        }

        // Calculate listings per second
        if ($duration > 0 && $updateData['total_listings_found'] > 0) {
            $updateData['listings_per_second'] = round($updateData['total_listings_found'] / ($duration / 1000), 2);
        }

        $crawlLog->update($updateData);

        Log::channel('crawler')->info('Crawl completed', [
            'crawl_log_id' => $crawlLog->id,
            'status' => $updateData['status'],
            'duration_ms' => $duration,
            'stats' => $updateData,
        ]);
    }

    /**
     * Mark a crawl as failed
     */
    public function failCrawl(CrawlLog $crawlLog, string $error, array $stats = []): void
    {
        $duration = $crawlLog->started_at->diffInMilliseconds(Carbon::now());
        
        $crawlLog->update([
            'status' => 'failed',
            'completed_at' => Carbon::now(),
            'duration_ms' => $duration,
            'hunted_deals_processed' => $stats['hunted_deals_processed'] ?? 0,
            'hunted_deals_failed' => $stats['hunted_deals_failed'] ?? 0,
            'total_errors' => ($stats['total_errors'] ?? 0) + 1,
            'errors' => array_merge($stats['errors'] ?? [], [$error]),
        ]);

        Log::channel('crawler')->error('Crawl failed', [
            'crawl_log_id' => $crawlLog->id,
            'error' => $error,
            'duration_ms' => $duration,
        ]);
    }

    /**
     * Get recent crawl statistics
     */
    public function getRecentStats(int $hours = 24): array
    {
        $logs = CrawlLog::recent($hours)
            ->orderBy('started_at', 'desc')
            ->get();

        return [
            'total_crawls' => $logs->count(),
            'successful_crawls' => $logs->where('status', 'completed')->where('total_errors', 0)->count(),
            'failed_crawls' => $logs->where('status', 'failed')->count(),
            'partial_success_crawls' => $logs->where('status', 'completed')->where('total_errors', '>', 0)->count(),
            'total_listings_found' => $logs->sum('total_listings_found'),
            'total_deals_created' => $logs->sum('new_deals_created'),
            'total_deals_updated' => $logs->sum('deals_updated'),
            'total_snapshots_created' => $logs->sum('snapshots_created'),
            'average_duration_ms' => $logs->where('duration_ms', '>', 0)->avg('duration_ms'),
            'average_success_rate' => $logs->where('success_rate', '>', 0)->avg('success_rate'),
            'last_crawl_at' => $logs->first()?->started_at,
        ];
    }

    /**
     * Clean up old crawl logs
     */
    public function cleanupOldLogs(int $daysToKeep = 30): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deletedCount = CrawlLog::where('started_at', '<', $cutoffDate)->delete();
        
        Log::channel('crawler')->info('Cleaned up old crawl logs', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString(),
        ]);
        
        return $deletedCount;
    }

    /**
     * Determine crawl status based on statistics
     */
    private function determineStatus(array $stats): string
    {
        $processed = $stats['hunted_deals_processed'] ?? 0;
        $failed = $stats['hunted_deals_failed'] ?? 0;
        $errors = $stats['total_errors'] ?? 0;

        if ($processed === 0) {
            return 'failed';
        }

        if ($failed === $processed) {
            return 'failed';
        }

        if ($errors > 0 || $failed > 0) {
            return 'partial';
        }

        return 'completed';
    }

    /**
     * Get current crawler configuration
     */
    private function getCurrentConfiguration(): array
    {
        return [
            'max_pages_per_search' => config('crawler.max_pages_per_search', 3),
            'request_delay_ms' => config('crawler.request_delay_ms', 2000),
            'max_listings_per_run' => config('crawler.max_listings_per_run', 100),
            'mcp_endpoint' => config('crawler.mcp_playwright_endpoint'),
            'user_agent' => config('crawler.user_agent'),
            'ai_classification_enabled' => config('features.ai_classification_enabled', true),
        ];
    }
}