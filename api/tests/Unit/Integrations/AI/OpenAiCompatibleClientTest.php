<?php

namespace Tests\Unit\Integrations\AI;

use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiChatRequest;
use App\Integrations\AI\OpenAICompatible\OpenAiCompatibleClient;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Support\Facades\Http;
use Mockery;
use Tests\TestCase;

class OpenAiCompatibleClientTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function test_parses_nullable_stage_and_should_reply_false(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => '',
                                'summary' => 'Cliente vai pensar.',
                                'should_reply' => false,
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $client = $this->makeClient();
        $response = $client->chat(new AiChatRequest(
            baseUrl: 'https://api.example.com/v1',
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [new AiChatMessage('system', 'test')],
        ));

        $this->assertFalse($response->shouldReply);
        $this->assertNull($response->suggestedStage);
        $this->assertSame('', $response->message);
        $this->assertSame('Cliente vai pensar.', $response->summary);
    }

    public function test_missing_stage_is_null_not_novo_lead(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => 'Olá!',
                                'summary' => 'Primeiro contato.',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->makeClient()->chat(new AiChatRequest(
            baseUrl: 'https://api.example.com/v1',
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [new AiChatMessage('system', 'test')],
        ));

        $this->assertNull($response->suggestedStage);
        $this->assertTrue($response->shouldReply);
    }

    public function test_accepts_stage_alias_and_applies_fallback_message(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'stage' => 'novo_lead',
                                'summary' => 'Cliente enviou oi repetidamente.',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        config(['ai.fallback_message' => 'Olá! Como posso ajudar?']);

        $response = $this->makeClient()->chat(new AiChatRequest(
            baseUrl: 'https://api.example.com/v1',
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [new AiChatMessage('system', 'test')],
        ));

        $this->assertSame('novo_lead', $response->suggestedStage);
        $this->assertSame('Olá! Como posso ajudar?', $response->message);
        $this->assertTrue($response->shouldReply);
    }

    public function test_empty_stage_string_is_treated_as_null(): void
    {
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    [
                        'message' => [
                            'content' => json_encode([
                                'message' => 'Ok.',
                                'suggested_stage' => '   ',
                                'summary' => 'Resumo.',
                            ]),
                        ],
                    ],
                ],
            ]),
        ]);

        $response = $this->makeClient()->chat(new AiChatRequest(
            baseUrl: 'https://api.example.com/v1',
            model: 'gpt-4o-mini',
            apiKey: 'test-key',
            messages: [new AiChatMessage('system', 'test')],
        ));

        $this->assertNull($response->suggestedStage);
    }

    private function makeClient(): OpenAiCompatibleClient
    {
        $logService = Mockery::mock(IntegrationLogService::class);
        $logService->shouldReceive('info')->andReturnNull();
        $logService->shouldReceive('error')->andReturnNull();

        return new OpenAiCompatibleClient(
            timeout: 5,
            integrationLogService: $logService,
        );
    }
}
