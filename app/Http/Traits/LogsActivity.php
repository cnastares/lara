<?php

namespace App\Http\Traits;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

trait LogsActivity
{
    /**
     * Get request identifier for logging
     */
    protected function getRequestId(Request $request = null): string
    {
        $request = $request ?? request();
        return $request->header('X-Request-Id') ?? Str::uuid()->toString();
    }

    /**
     * Get user context for logging
     */
    protected function getUserContext(): array
    {
        $user = auth()->user();
        return [
            'user_id' => $user?->id,
            'user_type' => $user ? class_basename($user) : 'guest',
            'ip' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ];
    }

    /**
     * Log successful action
     */
    protected function logSuccess(string $action, array $context = [], string $channel = 'default'): void
    {
        $logData = array_merge([
            'action' => $action,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        Log::channel($channel)->info("Action completed: {$action}", $logData);
    }

    /**
     * Log failed action
     */
    protected function logFailure(string $action, string $error, array $context = [], string $channel = 'default'): void
    {
        $logData = array_merge([
            'action' => $action,
            'error' => $error,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        Log::channel($channel)->warning("Action failed: {$action}", $logData);
    }

    /**
     * Log exception with context
     */
    protected function logException(string $action, Throwable $exception, array $context = [], string $channel = 'default'): void
    {
        $logData = array_merge([
            'action' => $action,
            'exception' => get_class($exception),
            'error' => $exception->getMessage(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        Log::channel($channel)->error("Exception in action: {$action}", $logData);
    }

    /**
     * Log request start
     */
    protected function logRequestStart(string $action, array $input = [], string $channel = 'default'): void
    {
        // Filter sensitive data
        $filteredInput = $this->filterSensitiveData($input);
        
        $logData = array_merge([
            'action' => $action,
            'method' => request()->method(),
            'url' => request()->fullUrl(),
            'input' => $filteredInput,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext());

        Log::channel($channel)->debug("Request started: {$action}", $logData);
    }

    /**
     * Log model operation
     */
    protected function logModelOperation(string $operation, string $model, $modelId = null, array $context = [], string $channel = 'default'): void
    {
        $logData = array_merge([
            'operation' => $operation,
            'model' => $model,
            'model_id' => $modelId,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        Log::channel($channel)->info("Model operation: {$operation} on {$model}", $logData);
    }

    /**
     * Log validation failure
     */
    protected function logValidationFailure(string $action, array $errors, array $input = [], string $channel = 'default'): void
    {
        $filteredInput = $this->filterSensitiveData($input);
        
        $logData = array_merge([
            'action' => $action,
            'validation_errors' => $errors,
            'input' => $filteredInput,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext());

        Log::channel($channel)->warning("Validation failed: {$action}", $logData);
    }

    /**
     * Log authentication event
     */
    protected function logAuthEvent(string $event, bool $success, array $context = []): void
    {
        $logData = array_merge([
            'event' => $event,
            'success' => $success,
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        $level = $success ? 'info' : 'warning';
        $message = $success ? "Auth success: {$event}" : "Auth failure: {$event}";

        Log::channel('auth')->{$level}($message, $logData);
    }

    /**
     * Log performance metrics
     */
    protected function logPerformance(string $action, float $executionTime, array $context = [], string $channel = 'performance'): void
    {
        $logData = array_merge([
            'action' => $action,
            'execution_time_ms' => round($executionTime * 1000, 2),
            'memory_usage_mb' => round(memory_get_usage(true) / 1024 / 1024, 2),
            'request_id' => $this->getRequestId(),
            'timestamp' => now()->toISOString(),
        ], $this->getUserContext(), $context);

        // Log as warning if execution takes more than 2 seconds
        $level = $executionTime > 2.0 ? 'warning' : 'info';
        Log::channel($channel)->{$level}("Performance: {$action}", $logData);
    }

    /**
     * Filter sensitive data from input
     */
    private function filterSensitiveData(array $data): array
    {
        $sensitiveFields = [
            'password',
            'password_confirmation',
            'token',
            'api_token',
            'access_token',
            'refresh_token',
            'secret',
            'key',
            'private_key',
            'credit_card',
            'card_number',
            'cvv',
            'ssn',
            'social_security',
        ];

        foreach ($sensitiveFields as $field) {
            if (isset($data[$field])) {
                $data[$field] = '[FILTERED]';
            }
        }

        return $data;
    }

    /**
     * Create performance timer closure
     */
    protected function withPerformanceLog(string $action, callable $callback, string $channel = 'performance')
    {
        $startTime = microtime(true);
        
        try {
            $result = $callback();
            $this->logPerformance($action, microtime(true) - $startTime, ['status' => 'success'], $channel);
            return $result;
        } catch (Throwable $e) {
            $this->logPerformance($action, microtime(true) - $startTime, ['status' => 'failed', 'error' => $e->getMessage()], $channel);
            throw $e;
        }
    }
}