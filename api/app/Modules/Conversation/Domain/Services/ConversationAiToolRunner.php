<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\AI\DTOs\AiStructuredResponse;
use App\Core\AI\DTOs\AiToolCall;
use App\Core\AI\Support\AiStructuredResponseParser;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
use InvalidArgumentException;

class ConversationAiToolRunner
{
    public function __construct(
        private readonly AiClient $aiClient,
        private readonly ExternalToolService $externalToolService,
        private readonly AiStructuredResponseParser $structuredResponseParser = new AiStructuredResponseParser,
    ) {}

    /**
     * @param  list<AiChatMessage>  $messages
     */
    public function run(
        int $companyId,
        string $baseUrl,
        string $model,
        string $apiKey,
        array $messages,
    ): AiStructuredResponse {
        $openAiTools = $this->externalToolService->toOpenAiTools($companyId);
        $hasTools = $openAiTools !== [];
        $allowedToolNames = array_values(array_map(
            fn (array $tool) => (string) ($tool['function']['name'] ?? ''),
            $openAiTools,
        ));
        $maxIterations = (int) config('integration.max_tool_iterations', 5);

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $expectTools = $hasTools && $iteration < ($maxIterations - 1);

            $completion = $this->aiClient->completion(new AiChatRequest(
                baseUrl: $baseUrl,
                model: $model,
                apiKey: $apiKey,
                messages: $messages,
                responseFormat: $expectTools ? null : 'json_object',
                tools: $expectTools ? $openAiTools : null,
                companyId: $companyId,
            ));

            if (! $completion->hasToolCalls()) {
                return $this->structuredResponseParser->parse($completion->content);
            }

            $messages[] = new AiChatMessage(
                role: 'assistant',
                content: $completion->content,
                toolCalls: $completion->toolCalls,
            );

            foreach ($completion->toolCalls as $toolCall) {
                $messages[] = new AiChatMessage(
                    role: 'tool',
                    content: $this->executeToolCall($companyId, $toolCall, $allowedToolNames),
                    toolCallId: $toolCall->id,
                );
            }
        }

        throw new InvalidArgumentException('Limite de execuções de ferramentas atingido.');
    }

    /**
     * @param  list<string>  $allowedToolNames
     */
    private function executeToolCall(int $companyId, AiToolCall $toolCall, array $allowedToolNames): string
    {
        $parameters = json_decode($toolCall->arguments, true);

        if (! is_array($parameters)) {
            $parameters = [];
        }

        $result = $this->externalToolService->execute(
            $companyId,
            $toolCall->name,
            $parameters,
            $allowedToolNames,
        );

        return $result->toToolMessageContent();
    }

}
