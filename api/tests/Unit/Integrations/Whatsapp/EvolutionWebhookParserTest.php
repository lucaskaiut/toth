<?php

namespace Tests\Unit\Integrations\Whatsapp;

use App\Integrations\Whatsapp\Evolution\EvolutionWebhookParser;
use PHPUnit\Framework\TestCase;

class EvolutionWebhookParserTest extends TestCase
{
    public function test_parses_incoming_text_message(): void
    {
        $parser = new EvolutionWebhookParser;

        $message = $parser->parse([
            'event' => 'messages.upsert',
            'instance' => 'minha-instancia',
            'data' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Olá',
                ],
                'pushName' => 'Maria',
            ],
        ]);

        $this->assertNotNull($message);
        $this->assertSame('minha-instancia', $message->instanceName);
        $this->assertSame('5511999999999', $message->phone);
        $this->assertSame('Olá', $message->content);
        $this->assertSame('Maria', $message->senderName);
    }

    public function test_ignores_messages_sent_by_instance(): void
    {
        $parser = new EvolutionWebhookParser;

        $message = $parser->parse([
            'event' => 'messages.upsert',
            'instance' => 'minha-instancia',
            'data' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => true,
                ],
                'message' => [
                    'conversation' => 'Eco',
                ],
            ],
        ]);

        $this->assertNull($message);
    }
}
