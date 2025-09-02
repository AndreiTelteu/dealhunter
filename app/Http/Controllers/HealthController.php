<?php

namespace App\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class HealthController extends Controller
{
    /**
     * Application health check endpoint
     */
    public function check(): JsonResponse
    {
        $checks = [
            'status' => 'ok',
            'timestamp' => now()->toISOString(),
            'version' => config('app.version', '1.0.0'),
            'environment' => config('app.env'),
            'checks' => []
        ];

        // Database connectivity check
        try {
            DB::connection()->getPdo();
            $checks['checks']['database'] = [
                'status' => 'ok',
                'message' => 'Database connection successful'
            ];
        } catch (\Exception $e) {
            $checks['status'] = 'error';
            $checks['checks']['database'] = [
                'status' => 'error',
                'message' => 'Database connection failed: ' . $e->getMessage()
            ];
        }

        // Cache connectivity check
        try {
            Cache::put('health_check', 'ok', 60);
            $cacheValue = Cache::get('health_check');
            
            if ($cacheValue === 'ok') {
                $checks['checks']['cache'] = [
                    'status' => 'ok',
                    'message' => 'Cache is working'
                ];
            } else {
                throw new \Exception('Cache value mismatch');
            }
        } catch (\Exception $e) {
            $checks['checks']['cache'] = [
                'status' => 'warning',
                'message' => 'Cache check failed: ' . $e->getMessage()
            ];
        }

        // Storage check
        try {
            $testFile = storage_path('logs/health_check.tmp');
            file_put_contents($testFile, 'health_check');
            
            if (file_exists($testFile)) {
                unlink($testFile);
                $checks['checks']['storage'] = [
                    'status' => 'ok',
                    'message' => 'Storage is writable'
                ];
            } else {
                throw new \Exception('File creation failed');
            }
        } catch (\Exception $e) {
            $checks['status'] = 'error';
            $checks['checks']['storage'] = [
                'status' => 'error',
                'message' => 'Storage check failed: ' . $e->getMessage()
            ];
        }

        // Application metrics
        $checks['metrics'] = [
            'memory_usage' => memory_get_usage(true),
            'memory_peak' => memory_get_peak_usage(true),
            'uptime' => $this->getUptime(),
        ];

        // Log health check if there are errors
        if ($checks['status'] !== 'ok') {
            Log::channel('security')->warning('Health check failed', $checks);
        }

        $httpStatus = $checks['status'] === 'ok' ? 200 : 503;
        
        return response()->json($checks, $httpStatus);
    }

    /**
     * Simple health check for load balancers
     */
    public function ping(): JsonResponse
    {
        return response()->json(['status' => 'ok', 'timestamp' => now()->toISOString()]);
    }

    /**
     * Get application uptime
     */
    private function getUptime(): ?int
    {
        if (function_exists('sys_getloadavg')) {
            $uptime = shell_exec('uptime -s 2>/dev/null');
            if ($uptime) {
                $startTime = strtotime(trim($uptime));
                return time() - $startTime;
            }
        }
        
        return null;
    }
}