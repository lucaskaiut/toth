<?php

namespace Tests\Unit\Core\AI;

use App\Core\AI\Support\AiStructuredResponseParser;
use InvalidArgumentException;
use Tests\TestCase;

class AiStructuredResponseParserTest extends TestCase
{
    private AiStructuredResponseParser $parser;

    protected function setUp(): void
    {
        parent::setUp();

        config(['ai.fallback_message' => 'Mensagem padrão de fallback.']);
        $this->parser = new AiStructuredResponseParser;
    }

    public function test_accepts_stage_alias_and_uses_fallback_when_message_is_missing(): void
    {
        $response = $this->parser->parse(json_encode([
            'stage' => 'novo_lead',
            'summary' => 'Cliente enviou 3 mensagens com oi sem contexto claro.',
        ]));

        $this->assertTrue($response->shouldReply);
        $this->assertSame('novo_lead', $response->suggestedStage);
        $this->assertSame('Mensagem padrão de fallback.', $response->message);
        $this->assertStringContainsString('3 mensagens', $response->summary);
    }

    public function test_keeps_explicit_should_reply_false_without_fallback(): void
    {
        $response = $this->parser->parse(json_encode([
            'summary' => 'Cliente vai pensar.',
            'should_reply' => false,
        ]));

        $this->assertFalse($response->shouldReply);
        $this->assertSame('', $response->message);
    }

    public function test_throws_for_invalid_json(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON válido');

        $this->parser->parse('not-json');
    }
}
