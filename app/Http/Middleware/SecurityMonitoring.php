<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\RateLimiter;
use Symfony\Component\HttpFoundation\Response;

class SecurityMonitoring
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Log suspicious activity
        $this->logSuspiciousActivity($request);

        // Check for rate limiting violations
        $this->checkRateLimit($request);

        $response = $next($request);

        // Log failed authentication attempts
        if ($response->getStatusCode() === 401) {
            Log::channel('security')->warning('Authentication failed', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        // Log access to sensitive endpoints
        if ($this->isSensitiveEndpoint($request)) {
            Log::channel('security')->info('Sensitive endpoint accessed', [
                'url' => $request->fullUrl(),
                'method' => $request->method(),
                'user_id' => $request->user()?->id,
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'timestamp' => now()->toISOString(),
            ]);
        }

        return $response;
    }

    /**
     * Log suspicious activity patterns
     */
    private function logSuspiciousActivity(Request $request): void
    {
        $suspiciousPatterns = [
            'sql injection' => '/(\bunion\b|\bselect\b|\binsert\b|\bupdate\b|\bdelete\b|\bdrop\b)/i',
            'xss attempt' => '/<script|javascript:|on\w+\s*=/i',
            'path traversal' => '/\.\.[\/\\\\]/i',
            'command injection' => '/[;&|`$()]/i',
        ];

        $requestData = $request->all();
        $queryString = $request->getQueryString();
        $userAgent = $request->userAgent();

        foreach ($suspiciousPatterns as $type => $pattern) {
            // Check query parameters
            if ($queryString && preg_match($pattern, $queryString)) {
                $this->logSecurityThreat($request, $type, 'query_string', $queryString);
            }

            // Check request data
            foreach ($requestData as $key => $value) {
                if (is_string($value) && preg_match($pattern, $value)) {
                    $this->logSecurityThreat($request, $type, $key, $value);
                }
            }
        }

        // User-Agent is intentionally not scanned with generic injection regexes:
        // normal browser identifiers contain semicolons and parentheses. Bot-like
        // User-Agents are handled separately below without false-positive alerts.

        // Check for bot-like behavior
        if ($this->isBotLikeBehavior($request)) {
            Log::channel('security')->warning('Bot-like behavior detected', [
                'ip' => $request->ip(),
                'user_agent' => $userAgent,
                'url' => $request->fullUrl(),
                'timestamp' => now()->toISOString(),
            ]);
        }
    }

    /**
     * Log security threats
     */
    private function logSecurityThreat(Request $request, string $type, string $field, string $value): void
    {
        Log::channel('security')->error('Security threat detected', [
            'threat_type' => $type,
            'field' => $field,
            'value' => substr($value, 0, 200), // Limit logged value length
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'url' => $request->fullUrl(),
            'method' => $request->method(),
            'user_id' => $request->user()?->id,
            'timestamp' => now()->toISOString(),
        ]);
    }

    /**
     * Check for rate limiting violations
     */
    private function checkRateLimit(Request $request): void
    {
        $key = 'security_monitor:'.$request->ip();
        $maxAttempts = 100; // requests per minute

        if (RateLimiter::tooManyAttempts($key, $maxAttempts)) {
            Log::channel('security')->warning('Rate limit exceeded', [
                'ip' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'url' => $request->fullUrl(),
                'attempts' => RateLimiter::attempts($key),
                'max_attempts' => $maxAttempts,
                'timestamp' => now()->toISOString(),
            ]);
        }

        RateLimiter::hit($key, 60); // 1 minute window
    }

    /**
     * Check if the endpoint is sensitive
     */
    private function isSensitiveEndpoint(Request $request): bool
    {
        $sensitivePatterns = [
            '/admin',
            '/api',
            '/health',
            '/login',
            '/register',
            '/password',
        ];

        $path = $request->path();

        foreach ($sensitivePatterns as $pattern) {
            if (str_starts_with($path, ltrim($pattern, '/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Detect bot-like behavior
     */
    private function isBotLikeBehavior(Request $request): bool
    {
        $userAgent = $request->userAgent();

        if (! $userAgent) {
            return true; // No user agent is suspicious
        }

        // Common bot patterns
        $botPatterns = [
            '/bot/i',
            '/crawler/i',
            '/spider/i',
            '/scraper/i',
            '/curl/i',
            '/wget/i',
            '/python/i',
        ];

        foreach ($botPatterns as $pattern) {
            if (preg_match($pattern, $userAgent)) {
                return true;
            }
        }

        return false;
    }
}
