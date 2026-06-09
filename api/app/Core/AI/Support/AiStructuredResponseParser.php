<?php

namespace App\Core\AI\Support;

use App\Core\AI\DTOs\AiStructuredResponse;
use InvalidArgumentException;

class AiStructuredResponseParser
{
    public function parse(string $content): AiStructuredResponse
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Resposta da IA não está em formato JSON válido.');
        }

        $shouldReply = $this->parseShouldReply($decoded);
        $message = trim((string) ($decoded['message'] ?? $decoded['resposta'] ?? ''));
        $stage = $this->parseStage($decoded);
        $summary = trim((string) ($decoded['summary'] ?? $decoded['resumo'] ?? ''));

        if ($shouldReply && $message === '') {
            $message = trim((string) config('ai.fallback_message', 'Olá! Como posso ajudar você hoje?'));
        }

        return new AiStructuredResponse(
            message: $message,
            suggestedStage: $stage,
            summary: $summary,
            shouldReply: $shouldReply && $message !== '',
        );
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function parseStage(array $decoded): ?string
    {
        $stageRaw = $decoded['suggested_stage']
            ?? $decoded['stage']
            ?? $decoded['estagio']
            ?? null;

        if (! is_string($stageRaw)) {
            return null;
        }

        $stage = trim($stageRaw);

        return $stage !== '' ? $stage : null;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function parseShouldReply(array $decoded): bool
    {
        if (! array_key_exists('should_reply', $decoded)) {
            return true;
        }

        $value = $decoded['should_reply'];

        if (is_bool($value)) {
            return $value;
        }

        if (is_string($value)) {
            return ! in_array(strtolower(trim($value)), ['false', '0', 'no', 'nao', 'não'], true);
        }

        return (bool) $value;
    }
}
