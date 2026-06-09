<?php

namespace Tests\Unit\Integrations\ExternalTools;

use App\Core\Integration\DTOs\IntegrationConnection;
use App\Core\Integration\Enums\ExternalIntegrationProvider;
use App\Integrations\ExternalTools\HttpExternalToolClient;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class HttpExternalToolClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_discovers_tools_from_external_api(): void
    {
        config([
            'integration.providers.nox.base_url' => 'https://nox.example',
        ]);

        Http::fake([
            'https://nox.example/api/integration/tools' => Http::response([
                'data' => [
                    [
                        'name' => 'check_availability',
                        'description' => 'Consulta horários',
                        'parameters' => [
                            [
                                'name' => 'date',
                                'description' => 'Data',
                                'type' => 'string',
                                'required' => true,
                            ],
                        ],
                    ],
                ],
            ]),
        ]);

        $client = $this->makeClient();
        $tools = $client->discoverTools($this->connection());

        $this->assertCount(1, $tools);
        $this->assertSame('check_availability', $tools[0]->name);
    }

    public function test_executes_tool_and_maps_success_response(): void
    {
        config([
            'integration.providers.nox.base_url' => 'https://nox.example',
        ]);

        Http::fake([
            'https://nox.example/api/integration/tools/execute' => Http::response([
                'success' => true,
                'data' => ['id' => 10, 'status' => 'pending'],
            ]),
        ]);

        $client = $this->makeClient();
        $result = $client->executeTool($this->connection(), 'create_scheduling', [
            'service_id' => 1,
            'date' => '2026-06-10 09:00',
        ]);

        $this->assertTrue($result->success);
        $this->assertSame(10, $result->data['id']);
    }

    private function connection(): IntegrationConnection
    {
        return new IntegrationConnection(
            provider: ExternalIntegrationProvider::Nox,
            apiToken: 'token-123',
            companyId: 1,
        );
    }

    private function makeClient(): HttpExternalToolClient
    {
        $logService = Mockery::mock(IntegrationLogService::class);
        $logService->shouldReceive('info')->andReturnNull();
        $logService->shouldReceive('error')->andReturnNull();

        return new HttpExternalToolClient($logService);
    }
}
