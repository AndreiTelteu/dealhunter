<?php

namespace App\Services;

use App\Models\SystemHealth;
use App\Services\Crawlers\OlxCrawlerService;
use App\Services\IntentClassifierService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

/**
 * Service for monitoring system health
 */
class SystemHealthService
{
    /**
     * Check all system components
     */
    public function checkAllComponents(): array
    {
        $results = [];
        
        $results['database'] = $this->checkDatabase();
        $results['mcp'] = $this->checkMcpConnection();
        $results['crawler'] = $this->checkCrawlerHealth();
        $results['ai'] = $this->checkAiService();
        
        return $results;
    }

    /**
     * Check database connectivity and performance
     */
    public function checkDatabase(): SystemHealth
    {
        $startTime = microtime(true);
        
        try {
            // Test basic connectivity
            DB::connection()->getPdo();
            
            // Test query performance
            $testQuery = DB::table('users')->limit(1)->get();
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            // Check for slow queries (>100ms is warning, >500ms is critical)
            $status = 'healthy';
            $message = 'Database connection is healthy';
            
            if ($responseTime > 500) {
                $status = 'critical';
                $message = 'Database queries are very slow';
            } elseif ($responseTime > 100) {
                $status = 'warning';
                $message = 'Database queries are slower than expected';
            }
            
            return $this->recordHealthCheck('database', $status, $message, (int) $responseTime, [
                'connection_name' => config('database.default'),
                'driver' => config('database.connections.' . config('database.default') . '.driver'),
                'query_time_ms' => round($responseTime, 2),
            ]);
            
        } catch (\Exception $e) {
            return $this->recordHealthCheck('database', 'critical', 'Database connection failed: ' . $e->getMessage(), null, [
                'error' => $e->getMessage(),
                'connection_name' => config('database.default'),
            ]);
        }
    }

    /**
     * Check MCP Playwright connection
     */
    public function checkMcpConnection(): SystemHealth
    {
        $startTime = microtime(true);
        $endpoint = config('crawler.mcp_playwright_endpoint');
        
        if (!$endpoint) {
            return $this->recordHealthCheck('mcp', 'critical', 'MCP endpoint not configured', null, [
                'endpoint' => null,
            ]);
        }
        
        try {
            // Try to connect to MCP endpoint
            $response = Http::timeout(10)->get($endpoint . '/health');
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            if ($response->successful()) {
                $status = 'healthy';
                $message = 'MCP connection is healthy';
                
                if ($responseTime > 2000) {
                    $status = 'warning';
                    $message = 'MCP connection is slow';
                }
            } else {
                $status = 'critical';
                $message = 'MCP endpoint returned error: ' . $response->status();
            }
            
            return $this->recordHealthCheck('mcp', $status, $message, (int) $responseTime, [
                'endpoint' => $endpoint,
                'status_code' => $response->status(),
                'response_time_ms' => round($responseTime, 2),
            ]);
            
        } catch (\Exception $e) {
            return $this->recordHealthCheck('mcp', 'critical', 'MCP connection failed: ' . $e->getMessage(), null, [
                'endpoint' => $endpoint,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check crawler service health
     */
    public function checkCrawlerHealth(): SystemHealth
    {
        try {
            // Check if crawler service can be instantiated
            $crawler = app(OlxCrawlerService::class);
            
            // Check recent crawl activity
            $recentCrawls = DB::table('crawl_logs')
                ->where('started_at', '>=', Carbon::now()->subHours(24))
                ->count();
            
            $lastSuccessfulCrawl = DB::table('crawl_logs')
                ->where('status', 'completed')
                ->where('total_errors', 0)
                ->orderBy('started_at', 'desc')
                ->first();
            
            $status = 'healthy';
            $message = 'Crawler service is operational';
            
            // Check if we haven't had a successful crawl in the last 2 hours
            if (!$lastSuccessfulCrawl || Carbon::parse($lastSuccessfulCrawl->started_at)->lt(Carbon::now()->subHours(2))) {
                $status = 'warning';
                $message = 'No recent successful crawls detected';
            }
            
            // Check if we haven't had any crawl attempts in the last 4 hours
            if ($recentCrawls === 0) {
                $status = 'critical';
                $message = 'No crawl activity in the last 24 hours';
            }
            
            return $this->recordHealthCheck('crawler', $status, $message, null, [
                'recent_crawls_24h' => $recentCrawls,
                'last_successful_crawl' => $lastSuccessfulCrawl?->started_at,
                'crawler_class' => get_class($crawler),
            ]);
            
        } catch (\Exception $e) {
            return $this->recordHealthCheck('crawler', 'critical', 'Crawler service error: ' . $e->getMessage(), null, [
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Check AI classification service
     */
    public function checkAiService(): SystemHealth
    {
        if (!config('features.ai_classification_enabled', true)) {
            return $this->recordHealthCheck('ai', 'unknown', 'AI classification is disabled', null, [
                'enabled' => false,
            ]);
        }
        
        $startTime = microtime(true);
        
        try {
            $classifier = app(IntentClassifierService::class);
            
            // Test with a simple classification
            $testResult = $classifier->classifyListing(
                'laptop',
                (object) [
                    'title' => 'Laptop Dell second hand',
                    'description' => 'Laptop in good condition, works perfectly'
                ]
            );
            
            $responseTime = (microtime(true) - $startTime) * 1000;
            
            $status = 'healthy';
            $message = 'AI classification service is working';
            
            if ($responseTime > 5000) {
                $status = 'warning';
                $message = 'AI classification is slow';
            }
            
            if (!$testResult || !isset($testResult->confidence)) {
                $status = 'critical';
                $message = 'AI classification returned invalid results';
            }
            
            return $this->recordHealthCheck('ai', $status, $message, (int) $responseTime, [
                'enabled' => true,
                'response_time_ms' => round($responseTime, 2),
                'test_confidence' => $testResult->confidence ?? null,
                'provider' => config('ai.provider'),
                'model' => config('ai.model'),
            ]);
            
        } catch (\Exception $e) {
            return $this->recordHealthCheck('ai', 'critical', 'AI service error: ' . $e->getMessage(), null, [
                'enabled' => true,
                'error' => $e->getMessage(),
                'provider' => config('ai.provider'),
            ]);
        }
    }

    /**
     * Get overall system health status
     */
    public function getOverallHealth(): array
    {
        $components = SystemHealth::getLatestForAllComponents();
        
        $healthyCount = 0;
        $warningCount = 0;
        $criticalCount = 0;
        $unknownCount = 0;
        
        foreach ($components as $component => $health) {
            if (!$health) {
                $unknownCount++;
                continue;
            }
            
            match ($health->status) {
                'healthy' => $healthyCount++,
                'warning' => $warningCount++,
                'critical' => $criticalCount++,
                default => $unknownCount++,
            };
        }
        
        $totalComponents = count($components);
        $overallStatus = 'healthy';
        
        if ($criticalCount > 0) {
            $overallStatus = 'critical';
        } elseif ($warningCount > 0) {
            $overallStatus = 'warning';
        } elseif ($unknownCount === $totalComponents) {
            $overallStatus = 'unknown';
        }
        
        return [
            'overall_status' => $overallStatus,
            'components' => $components,
            'summary' => [
                'healthy' => $healthyCount,
                'warning' => $warningCount,
                'critical' => $criticalCount,
                'unknown' => $unknownCount,
                'total' => $totalComponents,
            ],
            'last_check' => $this->getLastCheckTime($components),
        ];
    }

    /**
     * Clean up old health check records
     */
    public function cleanupOldHealthChecks(int $daysToKeep = 7): int
    {
        $cutoffDate = Carbon::now()->subDays($daysToKeep);
        
        $deletedCount = SystemHealth::where('checked_at', '<', $cutoffDate)->delete();
        
        Log::info('Cleaned up old health checks', [
            'deleted_count' => $deletedCount,
            'cutoff_date' => $cutoffDate->toISOString(),
        ]);
        
        return $deletedCount;
    }

    /**
     * Record a health check result
     */
    private function recordHealthCheck(
        string $component,
        string $status,
        string $message,
        ?int $responseTime = null,
        array $details = []
    ): SystemHealth {
        return SystemHealth::create([
            'component' => $component,
            'status' => $status,
            'message' => $message,
            'response_time_ms' => $responseTime,
            'details' => $details,
            'checked_at' => Carbon::now(),
        ]);
    }

    /**
     * Get the most recent check time across all components
     */
    private function getLastCheckTime(array $components): ?Carbon
    {
        $lastCheck = null;
        
        foreach ($components as $health) {
            if ($health && (!$lastCheck || $health->checked_at->gt($lastCheck))) {
                $lastCheck = $health->checked_at;
            }
        }
        
        return $lastCheck;
    }
}