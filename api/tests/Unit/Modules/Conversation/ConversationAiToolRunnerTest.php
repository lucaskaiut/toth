<?php

namespace Tests\Unit\Modules\Conversation;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiCompletionResponse;
use App\Core\AI\DTOs\AiToolCall;
use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use App\Core\Integration\DTOs\IntegrationConnection;
use App\Core\Integration\Enums\ExternalIntegrationProvider;
use App\Modules\Conversation\Domain\Services\ConversationAiToolRunner;
use App\Modules\ExternalIntegration\Domain\Services\CompanyIntegrationResolver;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
use Mockery;
use Tests\TestCase;

class ConversationAiToolRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_executes_tool_calls_before_final_json_response(): void
    {
        $connection = new IntegrationConnection(
            provider: ExternalIntegrationProvider::Nox,
            apiToken: 'token',
            companyId: 1,
        );

        $resolver = Mockery::mock(CompanyIntegrationResolver::class);
        $resolver->shouldReceive('resolve')->with(1)->andReturn($connection);

        $externalClient = Mockery::mock(ExternalToolClient::class);
        $externalClient->shouldReceive('discoverTools')->once()->andReturn([
            new ExternalToolDefinition(
                name: 'check_availability',
                description: 'Consulta horários',
                parameters: [],
            ),
        ]);
        $externalClient->shouldReceive('executeTool')->once()->andReturn(
            new ExternalToolExecutionResult(success: true, data: ['users' => []]),
        );

        $aiClient = Mockery::mock(AiClient::class);
        $aiClient->shouldReceive('completion')->twice()->andReturn(
            new AiCompletionResponse(
                content: '',
                toolCalls: [
                    new AiToolCall(
                        id: 'call_1',
                        name: 'check_availability',
                        arguments: '{"date":"2026-06-10","service_id":1}',
                    ),
                ],
            ),
            new AiCompletionResponse(
                content: json_encode([
                    'message' => 'Temos horário às 9h.',
                    'summary' => 'Cliente consultou disponibilidade.',
                    'should_reply' => true,
                ]),
            ),
        );

        $runner = new ConversationAiToolRunner(
            $aiClient,
            new ExternalToolService($externalClient, $resolver),
        );

        $response = $runner->run(
            companyId: 1,
            baseUrl: 'https://api.example.com/v1',
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [new AiChatMessage('system', 'test')],
        );

        $this->assertSame('Temos horário às 9h.', $response->message);
        $this->assertTrue($response->shouldReply);
    }
}
