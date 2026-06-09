<?php

namespace App\Integrations\AI\OpenAICompatible;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\AI\DTOs\AiCompletionResponse;
use App\Core\AI\DTOs\AiStructuredResponse;
use App\Core\AI\DTOs\AiToolCall;
use App\Core\AI\Support\AiStructuredResponseParser;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class OpenAiCompatibleClient implements AiClient
{
    public function __construct(
        private readonly int $timeout,
        private readonly IntegrationLogService $integrationLogService,
        private readonly AiStructuredResponseParser $structuredResponseParser = new AiStructuredResponseParser,
    ) {}

    public function chat(AiChatRequest $request): AiStructuredResponse
    {
        $completion = $this->completion($request);

        if ($completion->hasToolCalls()) {
            throw new InvalidArgumentException('Resposta da IA contém tool calls inesperadas.');
        }

        return $this->structuredResponseParser->parse($completion->content);
    }

    public function completion(AiChatRequest $request): AiCompletionResponse
    {
        $url = rtrim($request->baseUrl, '/').'/chat/completions';

        $payload = [
            'model' => $request->model,
            'messages' => array_map(
                fn (AiChatMessage $message) => $this->serializeMessage($message),
                $request->messages,
            ),
        ];

        if ($request->responseFormat === 'json_object') {
            $payload['response_format'] = ['type' => 'json_object'];
        }

        if ($request->tools !== null && $request->tools !== []) {
            $payload['tools'] = $request->tools;
            $payload['tool_choice'] = 'auto';
        }

        $this->integrationLogService->info(
            integration: 'ai',
            action: 'chat',
            message: 'AI request',
            context: [
                'url' => $url,
                'timeout_seconds' => $this->timeout,
                'headers' => [
                    'Authorization' => $this->maskToken($request->apiKey),
                    'Accept' => 'application/json',
                    'Content-Type' => 'application/json',
                ],
                'payload' => $payload,
            ],
            companyId: $request->companyId,
        );

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($request->apiKey)
                ->acceptJson()
                ->post($url, $payload);

            $this->logResponse($url, $response, $request->companyId);

            if (! $response->successful()) {
                $this->integrationLogService->error(
                    integration: 'ai',
                    action: 'chat',
                    message: 'Falha na API de IA.',
                    context: [
                        'url' => $url,
                        'status' => $response->status(),
                        'body' => $response->json() ?? $response->body(),
                    ],
                    companyId: $request->companyId,
                );

                throw new InvalidArgumentException('Não foi possível obter resposta da IA.');
            }

            return $this->parseCompletionResponse($response);
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: 'ai',
                action: 'chat',
                message: $exception->getMessage(),
                companyId: $request->companyId,
            );

            throw new InvalidArgumentException('Erro ao comunicar com a API de IA.', 0, $exception);
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function serializeMessage(AiChatMessage $message): array
    {
        if ($message->role === 'tool') {
            return [
                'role' => 'tool',
                'tool_call_id' => (string) $message->toolCallId,
                'content' => $message->content,
            ];
        }

        if ($message->toolCalls !== null && $message->toolCalls !== []) {
            return [
                'role' => $message->role,
                'content' => $message->content !== '' ? $message->content : null,
                'tool_calls' => array_map(
                    fn (AiToolCall $toolCall) => [
                        'id' => $toolCall->id,
                        'type' => 'function',
                        'function' => [
                            'name' => $toolCall->name,
                            'arguments' => $toolCall->arguments,
                        ],
                    ],
                    $message->toolCalls,
                ),
            ];
        }

        return [
            'role' => $message->role,
            'content' => $message->content,
        ];
    }

    private function parseCompletionResponse(Response $response): AiCompletionResponse
    {
        $message = $response->json('choices.0.message');

        if (! is_array($message)) {
            throw new InvalidArgumentException('Resposta da IA inválida.');
        }

        $content = (string) ($message['content'] ?? '');
        $toolCalls = [];

        foreach ($message['tool_calls'] ?? [] as $toolCall) {
            if (! is_array($toolCall)) {
                continue;
            }

            $function = $toolCall['function'] ?? null;

            if (! is_array($function)) {
                continue;
            }

            $toolCalls[] = new AiToolCall(
                id: (string) ($toolCall['id'] ?? ''),
                name: (string) ($function['name'] ?? ''),
                arguments: (string) ($function['arguments'] ?? '{}'),
            );
        }

        return new AiCompletionResponse(
            content: $content,
            toolCalls: $toolCalls,
        );
    }

    private function logResponse(string $url, Response $response, ?int $companyId = null): void
    {
        $context = [
            'url' => $url,
            'status' => $response->status(),
            'reason' => $response->reason(),
            'response_headers' => $response->headers(),
            'body' => $response->json() ?? $response->body(),
        ];

        if ($response->successful()) {
            $this->integrationLogService->info(
                integration: 'ai',
                action: 'chat',
                message: 'AI response OK',
                context: $context,
                companyId: $companyId,
            );

            return;
        }

        $this->integrationLogService->error(
            integration: 'ai',
            action: 'chat',
            message: 'AI response error',
            context: $context,
            companyId: $companyId,
        );
    }

    private function maskToken(string $token): string
    {
        if ($token === '') {
            return '(vazio)';
        }

        if (strlen($token) <= 12) {
            return '***';
        }

        return substr($token, 0, 6).'…'.substr($token, -4).' ('.strlen($token).' chars)';
    }
}
