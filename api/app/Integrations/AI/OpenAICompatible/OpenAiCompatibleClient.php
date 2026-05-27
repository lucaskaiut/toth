<?php

namespace App\Integrations\AI\OpenAICompatible;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\AI\DTOs\AiStructuredResponse;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class OpenAiCompatibleClient implements AiClient
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly int $timeout,
        private readonly IntegrationLogService $integrationLogService,
    ) {}

    public function chat(AiChatRequest $request): AiStructuredResponse
    {
        $payload = [
            'model' => $request->model,
            'messages' => array_map(
                fn ($message) => [
                    'role' => $message->role,
                    'content' => $message->content,
                ],
                $request->messages,
            ),
        ];

        if ($request->responseFormat === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($request->apiKey)
                ->acceptJson()
                ->post(rtrim($this->baseUrl, '/').'/chat/completions', $payload);

            if (! $response->successful()) {
                $this->integrationLogService->error(
                    integration: 'ai',
                    action: 'chat',
                    message: 'Falha na API de IA.',
                    context: [
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ],
                );

                throw new InvalidArgumentException('Não foi possível obter resposta da IA.');
            }

            $content = (string) ($response->json('choices.0.message.content') ?? '');

            return $this->parseStructuredContent($content);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: 'ai',
                action: 'chat',
                message: $exception->getMessage(),
            );

            throw new InvalidArgumentException('Erro ao comunicar com a API de IA.', 0, $exception);
        }
    }

    private function parseStructuredContent(string $content): AiStructuredResponse
    {
        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Resposta da IA não está em formato JSON válido.');
        }

        $message = trim((string) ($decoded['message'] ?? $decoded['resposta'] ?? ''));
        $stage = trim((string) ($decoded['suggested_stage'] ?? $decoded['estagio'] ?? 'novo_lead'));
        $summary = trim((string) ($decoded['summary'] ?? $decoded['resumo'] ?? ''));

        if ($message === '') {
            throw new InvalidArgumentException('Resposta da IA não contém mensagem.');
        }

        return new AiStructuredResponse(
            message: $message,
            suggestedStage: $stage,
            summary: $summary,
        );
    }
}
