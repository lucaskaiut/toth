<?php

namespace App\Core\AI\Support;

use App\Core\AI\DTOs\AiParseContext;
use App\Core\AI\DTOs\AiStructuredResponse;
use InvalidArgumentException;

class AiStructuredResponseParser
{
    public function parse(string $content, ?AiParseContext $context = null): AiStructuredResponse
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Resposta da IA não está em formato JSON válido.');
        }

        if ($this->isToolErrorResponse($decoded)) {
            return $this->buildToolFailureHandoff(
                summary: $this->buildToolFailureSummary($decoded),
                note: 'tool_error_json_echo',
            );
        }

        $shouldReply = $this->parseShouldReply($decoded);
        $message = trim((string) ($decoded['message'] ?? $decoded['resposta'] ?? ''));
        $stage = $this->parseStage($decoded);
        $summary = trim((string) ($decoded['summary'] ?? $decoded['resumo'] ?? ''));

        if ($shouldReply && $message === '') {
            if ($context?->toolFailed || $context?->hadToolActivity) {
                return $this->buildToolFailureHandoff(
                    summary: $summary !== ''
                        ? $summary
                        : 'Falha em ferramenta externa; atendimento encaminhado para humano.',
                    note: 'missing_message_with_tool_context',
                );
            }

            $message = trim((string) config('ai.fallback_message', 'Olá! Como posso ajudar você hoje?'));

            return new AiStructuredResponse(
                message: $message,
                suggestedStage: $stage,
                summary: $summary !== '' ? $summary : 'Resposta automática com fallback genérico.',
                shouldReply: true,
                isGenericFallback: true,
                parseNote: 'generic_fallback_applied',
            );
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

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function isToolErrorResponse(array $decoded): bool
    {
        return array_key_exists('success', $decoded) && $decoded['success'] === false;
    }

    /**
     * @param  array<string, mixed>  $decoded
     */
    private function buildToolFailureSummary(array $decoded): string
    {
        $error = $decoded['error'] ?? null;
        $errorMessage = is_array($error)
            ? trim((string) ($error['message'] ?? $error['detail'] ?? ''))
            : '';

        return $errorMessage !== ''
            ? "Falha em ferramenta externa: {$errorMessage}"
            : 'Falha em ferramenta externa; atendimento encaminhado para humano.';
    }

    private function buildToolFailureHandoff(string $summary, string $note): AiStructuredResponse
    {
        return new AiStructuredResponse(
            message: trim((string) config(
                'ai.tool_error_handoff_message',
                'Não consegui consultar a agenda agora. Vou encaminhar seu atendimento para nossa equipe humana.',
            )),
            suggestedStage: null,
            summary: $summary,
            shouldReply: true,
            requiresHandoff: true,
            parseNote: $note,
        );
    }
}
