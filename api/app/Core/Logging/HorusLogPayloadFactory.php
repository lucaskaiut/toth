<?php

namespace App\Core\Logging;

use Throwable;

class HorusLogPayloadFactory
{
    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    public function make(
        string $level,
        string $message,
        array $context = [],
        ?Throwable $exception = null,
    ): array {
        $request = app()->bound('request') ? request() : null;

        return array_filter([
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'entity_name' => $context['entity_name'] ?? null,
            'entity_id' => isset($context['entity_id']) ? (string) $context['entity_id'] : null,
            'source' => (string) config('horus.source', config('app.name', 'toth')),
            'environment' => (string) config('horus.environment', config('app.env', 'local')),
            'channel' => $context['channel'] ?? 'laravel',
            'request_id' => $context['request_id'] ?? ($request?->header('X-Request-Id') ?: null),
            'trace_id' => $context['trace_id'] ?? ($request?->header('X-Trace-Id') ?: null),
            'user_id' => $context['user_id'] ?? (auth()->check() ? (string) auth()->id() : null),
            'ip_address' => $context['ip_address'] ?? $request?->ip(),
            'user_agent' => $context['user_agent'] ?? $request?->userAgent(),
            'exception' => $exception ? [
                'class' => $exception::class,
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->getLine(),
                'stack_trace' => $exception->getTraceAsString(),
            ] : null,
        ], fn ($value) => $value !== null);
    }
}

