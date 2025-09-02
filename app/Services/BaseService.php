<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Base service class providing common functionality for all services
 * 
 * Includes error handling, logging, and common patterns
 */
abstract class BaseService
{
    /**
     * Service name for logging context
     */
    protected string $serviceName;
    
    /**
     * Default log channel
     */
    protected string $logChannel = 'default';
    
    public function __construct()
    {
        $this->serviceName = class_basename(static::class);
    }
    
    /**
     * Execute a service operation with error handling and logging
     * 
     * @param callable $operation
     * @param array $context
     * @param string $operationName
     * @return mixed
     * @throws ServiceException
     */
    protected function executeWithErrorHandling(callable $operation, array $context = [], string $operationName = 'operation')
    {
        $startTime = microtime(true);
        
        $this->logInfo("Starting {$operationName}", $context);
        
        try {
            $result = $operation();
            
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logInfo("Completed {$operationName}", array_merge($context, [
                'duration_ms' => $duration,
                'success' => true
            ]));
            
            return $result;
        } catch (Throwable $e) {
            $duration = round((microtime(true) - $startTime) * 1000, 2);
            $this->logError("Failed {$operationName}", array_merge($context, [
                'duration_ms' => $duration,
                'error' => $e->getMessage(),
                'exception_class' => get_class($e)
            ]), $e);
            
            throw new ServiceException(
                "Service operation failed: {$operationName}",
                previous: $e,
                context: $context
            );
        }
    }
    
    /**
     * Validate required parameters
     * 
     * @param array $params
     * @param array $required
     * @throws ServiceException
     */
    protected function validateRequired(array $params, array $required): void
    {
        $missing = [];
        
        foreach ($required as $key) {
            if (!array_key_exists($key, $params) || $params[$key] === null || $params[$key] === '') {
                $missing[] = $key;
            }
        }
        
        if (!empty($missing)) {
            throw new ServiceException(
                "Missing required parameters: " . implode(', ', $missing),
                context: ['missing_params' => $missing, 'provided_params' => array_keys($params)]
            );
        }
    }
    
    /**
     * Retry operation with exponential backoff
     * 
     * @param callable $operation
     * @param int $maxAttempts
     * @param int $baseDelayMs
     * @param array $context
     * @return mixed
     */
    protected function retryWithBackoff(callable $operation, int $maxAttempts = 3, int $baseDelayMs = 1000, array $context = [])
    {
        $attempt = 1;
        
        while ($attempt <= $maxAttempts) {
            try {
                return $operation();
            } catch (Throwable $e) {
                if ($attempt === $maxAttempts) {
                    $this->logError("All retry attempts failed", array_merge($context, [
                        'attempts' => $attempt,
                        'final_error' => $e->getMessage()
                    ]), $e);
                    throw $e;
                }
                
                $delay = $baseDelayMs * pow(2, $attempt - 1);
                $this->logWarning("Retry attempt {$attempt} failed, retrying in {$delay}ms", array_merge($context, [
                    'attempt' => $attempt,
                    'delay_ms' => $delay,
                    'error' => $e->getMessage()
                ]));
                
                usleep($delay * 1000); // Convert to microseconds
                $attempt++;
            }
        }
    }
    
    /**
     * Log info message with service context
     * 
     * @param string $message
     * @param array $context
     */
    protected function logInfo(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->info($message, $this->addServiceContext($context));
    }
    
    /**
     * Log warning message with service context
     * 
     * @param string $message
     * @param array $context
     */
    protected function logWarning(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->warning($message, $this->addServiceContext($context));
    }
    
    /**
     * Log error message with service context
     * 
     * @param string $message
     * @param array $context
     * @param Throwable|null $exception
     */
    protected function logError(string $message, array $context = [], ?Throwable $exception = null): void
    {
        $errorContext = $this->addServiceContext($context);
        
        if ($exception) {
            $errorContext['exception'] = [
                'class' => get_class($exception),
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'trace' => $exception->getTraceAsString()
            ];
        }
        
        Log::channel($this->logChannel)->error($message, $errorContext);
    }
    
    /**
     * Log debug message with service context
     * 
     * @param string $message
     * @param array $context
     */
    protected function logDebug(string $message, array $context = []): void
    {
        Log::channel($this->logChannel)->debug($message, $this->addServiceContext($context));
    }
    
    /**
     * Add service context to log data
     * 
     * @param array $context
     * @return array
     */
    private function addServiceContext(array $context): array
    {
        return array_merge([
            'service' => $this->serviceName,
            'timestamp' => now()->toISOString()
        ], $context);
    }
    
    /**
     * Sanitize sensitive data for logging
     * 
     * @param array $data
     * @return array
     */
    protected function sanitizeForLogging(array $data): array
    {
        $sensitive = ['password', 'token', 'key', 'secret', 'api_key'];
        
        foreach ($data as $key => $value) {
            if (is_string($key)) {
                $lowerKey = strtolower($key);
                foreach ($sensitive as $sensitiveKey) {
                    if (str_contains($lowerKey, $sensitiveKey)) {
                        $data[$key] = '[REDACTED]';
                        break;
                    }
                }
            }
            
            if (is_array($value)) {
                $data[$key] = $this->sanitizeForLogging($value);
            }
        }
        
        return $data;
    }
    
    /**
     * Format bytes for human-readable logging
     * 
     * @param int $bytes
     * @return string
     */
    protected function formatBytes(int $bytes): string
    {
        $units = ['B', 'KB', 'MB', 'GB'];
        $unitIndex = 0;
        
        while ($bytes >= 1024 && $unitIndex < count($units) - 1) {
            $bytes /= 1024;
            $unitIndex++;
        }
        
        return round($bytes, 2) . ' ' . $units[$unitIndex];
    }
    
    /**
     * Get memory usage for logging
     * 
     * @return array
     */
    protected function getMemoryUsage(): array
    {
        return [
            'current' => $this->formatBytes(memory_get_usage()),
            'peak' => $this->formatBytes(memory_get_peak_usage()),
            'current_real' => $this->formatBytes(memory_get_usage(true)),
            'peak_real' => $this->formatBytes(memory_get_peak_usage(true))
        ];
    }
}

/**
 * Custom exception for service errors
 */
class ServiceException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
        public readonly array $context = []
    ) {
        parent::__construct($message, $code, $previous);
    }
    
    /**
     * Get exception context
     * 
     * @return array
     */
    public function getContext(): array
    {
        return $this->context;
    }
}