<?php

namespace Tests\Unit\Core\AI;

use App\Core\AI\DTOs\AiParseContext;
use App\Core\AI\Support\AiStructuredResponseParser;
use InvalidArgumentException;
use Tests\TestCase;

class AiStructuredResponseParserTest extends TestCase
{
    private AiStructuredResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'ai.fallback_message' => 'Fallback genérico.',
            'ai.tool_error_handoff_message' => 'Não consegui consultar a agenda agora. Vou encaminhar para a equipe.',
        ]);
        $this->parser = new AiStructuredResponseParser;
    }

    public function test_accepts_stage_alias_and_uses_fallback_when_message_is_missing(): void
    {
        $response = $this->parser->parse(json_encode([
            'stage' => 'novo_lead',
            'summary' => 'Cliente enviou 3 mensagens com oi sem contexto claro.',
        ]));

        $this->assertTrue($response->isGenericFallback);
        $this->assertSame('Fallback genérico.', $response->message);
        $this->assertSame('novo_lead', $response->suggestedStage);
    }

    public function test_missing_message_with_tool_context_triggers_handoff_not_generic_fallback(): void
    {
        $response = $this->parser->parse(
            json_encode([
                'stage' => 'novo_lead',
                'summary' => 'Cliente pediu disponibilidade para consulta.',
            ]),
            new AiParseContext(hadToolActivity: true, toolFailed: true),
        );

        $this->assertFalse($response->isGenericFallback);
        $this->assertTrue($response->requiresHandoff);
        $this->assertSame(
            'Não consegui consultar a agenda agora. Vou encaminhar para a equipe.',
            $response->message,
        );
    }

    public function test_tool_error_response_triggers_handoff_instead_of_generic_fallback(): void
    {
        $response = $this->parser->parse(json_encode([
            'success' => false,
            'error' => ['message' => 'Timeout na API externa'],
        ]));

        $this->assertTrue($response->requiresHandoff);
        $this->assertFalse($response->isGenericFallback);
        $this->assertStringContainsString('Timeout na API externa', $response->summary);
    }

    public function test_keeps_explicit_should_reply_false_without_fallback(): void
    {
        $response = $this->parser->parse(json_encode([
            'summary' => 'Cliente vai pensar.',
            'should_reply' => false,
        ]));

        $this->assertFalse($response->shouldReply);
        $this->assertSame('', $response->message);
        $this->assertFalse($response->isGenericFallback);
    }

    public function test_explicit_requires_handoff_is_parsed(): void
    {
        $response = $this->parser->parse(json_encode([
            'message' => 'Vou encaminhar para nossa equipe confirmar o agendamento.',
            'suggested_stage' => 'proposta',
            'summary' => 'Cliente solicitou agendamento de consulta.',
            'should_reply' => true,
            'requires_handoff' => true,
        ]));

        $this->assertTrue($response->requiresHandoff);
        $this->assertFalse($response->isGenericFallback);
    }

    public function test_requires_handoff_defaults_to_false_when_omitted(): void
    {
        $response = $this->parser->parse(json_encode([
            'message' => 'Olá!',
            'summary' => 'Saudação.',
        ]));

        $this->assertFalse($response->requiresHandoff);
    }

    public function test_throws_for_invalid_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON válido');

        $this->parser->parse('not-json');
    }
}
