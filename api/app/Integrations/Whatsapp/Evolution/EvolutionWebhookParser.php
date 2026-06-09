<?php

namespace App\Integrations\Whatsapp\Evolution;

use App\Core\Whatsapp\DTOs\IncomingWhatsAppMessage;

class EvolutionWebhookParser
{
    public function parse(array $payload): ?IncomingWhatsAppMessage
    {
        $event = $payload['event'] ?? $payload['type'] ?? null;

        if (! in_array($event, ['messages.upsert', 'message', 'MESSAGES_UPSERT'], true)) {
            return null;
        }

        $instanceName = (string) ($payload['instance'] ?? $payload['instanceName'] ?? '');

        $data = $payload['data'] ?? $payload;

        if (isset($data[0]) && is_array($data[0])) {
            $data = $data[0];
        }

        $key = $data['key'] ?? [];
        $remoteJid = (string) ($key['remoteJid'] ?? $data['remoteJid'] ?? '');

        if ($remoteJid === '') {
            return null;
        }

        // Ignora outbound (fromMe): evita loop IA → WhatsApp → webhook → IA.
        if (($key['fromMe'] ?? $data['fromMe'] ?? false) === true) {
            return null;
        }

        $phone = $this->normalizePhone($remoteJid);
        $content = $this->extractText($data);

        if ($content === '') {
            return null;
        }

        $pushName = $data['pushName'] ?? $data['senderName'] ?? null;

        return new IncomingWhatsAppMessage(
            instanceName: $instanceName,
            phone: $phone,
            content: $content,
            senderName: is_string($pushName) ? $pushName : null,
            externalId: isset($key['id']) ? (string) $key['id'] : null,
        );
    }

    private function extractText(array $data): string
    {
        $message = $data['message'] ?? [];

        if (isset($message['conversation'])) {
            return trim((string) $message['conversation']);
        }

        if (isset($message['extendedTextMessage']['text'])) {
            return trim((string) $message['extendedTextMessage']['text']);
        }

        if (isset($data['body'])) {
            return trim((string) $data['body']);
        }

        if (isset($data['text'])) {
            return trim((string) $data['text']);
        }

        return '';
    }

    private function normalizePhone(string $remoteJid): string
    {
        $phone = preg_replace('/@.+$/', '', $remoteJid) ?? $remoteJid;
        $phone = preg_replace('/\D+/', '', $phone) ?? $phone;

        return $phone;
    }
}
