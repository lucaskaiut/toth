<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiChatRequest;
use App\Core\AI\DTOs\AiParseContext;
use App\Core\AI\DTOs\AiStructuredResponse;
use App\Core\AI\DTOs\AiToolCall;
use App\Core\AI\Support\AiStructuredResponseParser;
use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;

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
        $hadToolActivity = false;
        $toolFailed = false;

        for ($iteration = 0; $iteration < $maxIterations; $iteration++) {
            $isFinalIteration = $iteration === ($maxIterations - 1);
            $expectTools = $hasTools && ! $isFinalIteration;
            $requestMessages = $messages;

            if ($isFinalIteration) {
                $requestMessages[] = new AiChatMessage(
                    role: 'system',
                    content: trim((string) config('ai.final_tool_response_instructions')),
                );
            }

            $completion = $this->aiClient->completion(new AiChatRequest(
                baseUrl: $baseUrl,
                model: $model,
                apiKey: $apiKey,
                messages: $requestMessages,
                responseFormat: $expectTools ? null : 'json_object',
                tools: $expectTools ? $openAiTools : null,
                companyId: $companyId,
            ));

            if (! $completion->hasToolCalls()) {
                return $this->structuredResponseParser->parse(
                    $completion->content,
                    new AiParseContext(
                        hadToolActivity: $hadToolActivity,
                        toolFailed: $toolFailed,
                    ),
                );
            }

            $hadToolActivity = true;

            $messages[] = new AiChatMessage(
                role: 'assistant',
                content: $completion->content,
                toolCalls: $completion->toolCalls,
            );

            foreach ($completion->toolCalls as $toolCall) {
                $result = $this->executeToolCall($companyId, $toolCall, $allowedToolNames);

                if (! $result->success) {
                    $toolFailed = true;
                }

                $messages[] = new AiChatMessage(
                    role: 'tool',
                    content: $result->toLlmInstructionContent(),
                    toolCallId: $toolCall->id,
                );
            }
        }

        return $this->structuredResponseParser->parse('{"success":false}', new AiParseContext(
            hadToolActivity: true,
            toolFailed: true,
        ));
    }

    /**
     * @param  list<string>  $allowedToolNames
     */
    private function executeToolCall(int $companyId, AiToolCall $toolCall, array $allowedToolNames): ExternalToolExecutionResult
    {
        $parameters = json_decode($toolCall->arguments, true);

        if (! is_array($parameters)) {
            $parameters = [];
        }

        return $this->externalToolService->execute(
            $companyId,
            $toolCall->name,
            $parameters,
            $allowedToolNames,
        );
    }
}
