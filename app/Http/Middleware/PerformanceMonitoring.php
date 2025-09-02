<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class PerformanceMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $startTime = microtime(true);
        $startMemory = memory_get_usage(true);

        $response = $next($request);

        $endTime = microtime(true);
        $endMemory = memory_get_usage(true);

        $executionTime = ($endTime - $startTime) * 1000; // Convert to milliseconds
        $memoryUsage = $endMemory - $startMemory;

        // Log slow requests (over 1 second)
        if ($executionTime > 1000) {
            Log::channel('performance')->warning('Slow request detected', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage_bytes' => $memoryUsage,
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ]);
        }

        // Log all requests in production for monitoring
        if (app()->environment('production')) {
            Log::channel('performance')->info('Request processed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'status_code' => $response->getStatusCode(),
                'execution_time_ms' => round($executionTime, 2),
                'memory_usage_bytes' => $memoryUsage,
                'user_id' => $request->user()?->id,
            ]);
        }

        // Add performance headers for debugging
        if (config('app.debug')) {
            $response->headers->set('X-Execution-Time', round($executionTime, 2) . 'ms');
            $response->headers->set('X-Memory-Usage', $this->formatBytes($memoryUsage));
            $response->headers->set('X-Memory-Peak', $this->formatBytes(memory_get_peak_usage(true)));
        }

        return $response;
    }

    /**
     * Format bytes to human readable format
     */
    private function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);

        $bytes /= (1 << (10 * $pow));

        return round($bytes, 2) . ' ' . $units[$pow];
    }
}