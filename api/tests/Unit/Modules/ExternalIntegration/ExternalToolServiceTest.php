<?php

namespace Tests\Unit\Modules\ExternalIntegration;

use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\IntegrationConnection;
use App\Core\Integration\Enums\ExternalIntegrationProvider;
use App\Modules\ExternalIntegration\Domain\Services\CompanyIntegrationResolver;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
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
        $connection = new IntegrationConnection(
            provider: ExternalIntegrationProvider::Nox,
            apiToken: 'token',
            companyId: 1,
        );

        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->once()->with(1)->andReturn($connection);

        $client = Mockery::mock(ExternalToolClient::class);
        $client->shouldReceive('discoverTools')->once()->with($connection)->andReturn([
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
                    [
                        'name' => 'user_id',
                        'description' => 'Profissional',
                        'type' => 'integer',
                        'required' => false,
                    ],
                ],
            ),
        ]);

        $service = new ExternalToolService($client, $resolver);
        $tools = $service->toOpenAiTools(1);

        $this->assertCount(1, $tools);
        $this->assertSame('function', $tools[0]['type']);
        $this->assertSame('create_scheduling', $tools[0]['function']['name']);
        $this->assertSame(['service_id'], $tools[0]['function']['parameters']['required']);
        $this->assertSame('integer', $tools[0]['function']['parameters']['properties']['service_id']['type']);
    }

    public function test_rejects_unknown_tool_names(): void
    {
        $connection = new IntegrationConnection(
            provider: ExternalIntegrationProvider::Nox,
            apiToken: 'token',
            companyId: 1,
        );

        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->once()->with(1)->andReturn($connection);

        $client = Mockery::mock(ExternalToolClient::class);
        $client->shouldNotReceive('executeTool');

        $service = new ExternalToolService($client, $resolver);

        $result = $service->execute(1, 'unknown_tool', [], ['create_scheduling']);

        $this->assertFalse($result->success);
        $this->assertSame('Ferramenta não disponível.', $result->error['message'] ?? null);
    }

    public function test_returns_empty_tools_when_integration_is_not_configured(): void
    {
        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->once()->with(1)->andReturnNull();

        $client = Mockery::mock(ExternalToolClient::class);
        $client->shouldNotReceive('discoverTools');

        $service = new ExternalToolService($client, $resolver);

        $this->assertSame([], $service->toOpenAiTools(1));
    }
}
