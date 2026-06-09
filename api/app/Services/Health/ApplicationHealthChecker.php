<?php

namespace App\Services\Health;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redis;
use Illuminate\Support\Str;
use Throwable;

class ApplicationHealthChecker
{
    /**
     * @return array{healthy: bool, checks: array<string, array<string, mixed>>}
     */
    public function run(): array
    {
        $checks = [
            'database' => $this->checkDatabase(),
            'cache' => $this->checkCache(),
        ];

        if ($this->applicationRequiresRedis()) {
            $checks['redis'] = $this->checkRedis();
        } else {
            $checks['redis'] = [
                'status' => 'skipped',
                'reason' => 'Queue, cache and session are not using Redis.',
            ];
        }

        $healthy = collect($checks)->every(function (array $check): bool {
            if (($check['status'] ?? '') === 'skipped') {
                return true;
            }

            return ($check['status'] ?? '') === 'pass';
        });

        return [
            'healthy' => $healthy,
            'checks' => $checks,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function checkDatabase(): array
    {
        $start = hrtime(true);

        try {
            DB::connection()->getPdo();
            DB::select('select 1');

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($e),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkCache(): array
    {
        $start = hrtime(true);
        $key = 'health:'.Str::uuid()->toString();

        try {
            Cache::put($key, true, 5);
            $value = Cache::get($key);
            Cache::forget($key);

            if ($value !== true) {
                return [
                    'status' => 'fail',
                    'latency_ms' => $this->elapsedMs($start),
                    'message' => 'Cache read did not match written value.',
                ];
            }

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($e),
            ];
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function checkRedis(): array
    {
        $start = hrtime(true);

        try {
            Redis::connection()->ping();

            return [
                'status' => 'pass',
                'latency_ms' => $this->elapsedMs($start),
            ];
        } catch (Throwable $e) {
            report($e);

            return [
                'status' => 'fail',
                'latency_ms' => $this->elapsedMs($start),
                'message' => $this->safeMessage($e),
            ];
        }
    }

    private function applicationRequiresRedis(): bool
    {
        if (config('queue.default') === 'redis') {
            return true;
        }

        if (config('session.driver') === 'redis') {
            return true;
        }

        $store = config('cache.default');

        return is_string($store)
            && (config("cache.stores.{$store}.driver") ?? null) === 'redis';
    }

    private function elapsedMs(float $startHrTime): float
    {
        return round((hrtime(true) - $startHrTime) / 1_000_000, 2);
    }

    private function safeMessage(Throwable $e): string
    {
        if (config('app.debug')) {
            return $e->getMessage();
        }

        return 'Check failed.';
    }
}
