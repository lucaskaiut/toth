<?php

namespace Tests\Feature\Api\Health;

use Tests\TestCase;

class HealthCheckTest extends TestCase
{
    public function test_health_returns_healthy_when_database_and_cache_are_available(): void
    {
        $response = $this->getJson('/api/health');

        $response->assertOk();
        $response->assertJsonPath('status', 'healthy');
        $response->assertJsonStructure([
            'status',
            'checks' => [
                'database' => ['status', 'latency_ms'],
                'cache' => ['status', 'latency_ms'],
                'redis',
            ],
        ]);
        $this->assertSame('pass', $response->json('checks.database.status'));
        $this->assertSame('pass', $response->json('checks.cache.status'));
    }
}
