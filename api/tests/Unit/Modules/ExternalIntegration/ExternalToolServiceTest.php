<?php

namespace Tests\Unit\Modules\ExternalIntegration;

use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\IntegrationConnection;
use App\Core\Integration\Enums\ExternalIntegrationProvider;
use App\Modules\ExternalIntegration\Domain\Services\CompanyIntegrationResolver;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolParameterValidator;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Mockery;
use Tests\TestCase;

class ExternalToolServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_converts_external_tools_to_openai_format(): void
    {
        $service = $this->makeService(
            connection: $this->connection(),
            discoveredTools: [
                new ExternalToolDefinition(
                    name: 'create_scheduling',
                    description: 'Cria agendamento',
                    parameters: [
                        [
                            'name' => 'service_id',
                            'description' => 'ID do serviço',
                            'type' => 'integer',
                            'required' => true,
                        ],
                    ],
                ),
            ],
        );

        $tools = $service->toOpenAiTools(1);

        $this->assertCount(1, $tools);
        $this->assertSame('create_scheduling', $tools[0]['function']['name']);
    }

    public function test_rejects_invalid_parameters_without_calling_provider(): void
    {
        $client = Mockery::mock(ExternalToolClient::class);
        $client->shouldReceive('discoverTools')->andReturn([
            new ExternalToolDefinition(
                name: 'check_availability',
                description: 'Consulta horários',
                parameters: [
                    [
                        'name' => 'date',
                        'description' => 'Data',
                        'type' => 'string',
                        'required' => true,
                    ],
                ],
            ),
        ]);
        $client->shouldNotReceive('executeTool');

        $service = $this->makeService(connection: $this->connection(), client: $client);
        $result = $service->execute(1, 'check_availability', ['date' => 'YYYY-MM-DD'], ['check_availability']);

        $this->assertFalse($result->success);
        $this->assertTrue($result->isValidationError());
    }

    public function test_returns_empty_tools_when_integration_is_not_configured(): void
    {
        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->once()->with(1)->andReturnNull();

        $client = Mockery::mock(ExternalToolClient::class);
        $client->shouldNotReceive('discoverTools');

        $service = new ExternalToolService(
            $client,
            $resolver,
            new ExternalToolParameterValidator,
            $this->logService(),
        );

        $this->assertSame([], $service->toOpenAiTools(1));
    }

    private function makeService(
        ?IntegrationConnection $connection = null,
        ?ExternalToolClient $client = null,
        array $discoveredTools = [],
    ): ExternalToolService {
        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->andReturn($connection);

        $client ??= Mockery::mock(ExternalToolClient::class);
        $client->shouldReceive('discoverTools')->andReturn($discoveredTools);

        return new ExternalToolService(
            $client,
            $resolver,
            new ExternalToolParameterValidator,
            $this->logService(),
        );
    }

    private function connection(): IntegrationConnection
    {
        return new IntegrationConnection(
            provider: ExternalIntegrationProvider::Nox,
            apiToken: 'token',
            companyId: 1,
        );
    }

    private function logService(): IntegrationLogService
    {
        $logService = Mockery::mock(IntegrationLogService::class);
        $logService->shouldReceive('info')->andReturnNull();
        $logService->shouldReceive('error')->andReturnNull();

        return $logService;
    }
}
