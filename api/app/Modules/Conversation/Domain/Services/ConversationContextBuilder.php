<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\DTOs\AiChatMessage;
use App\Modules\CompanyConfig\Domain\Services\CompanyAiConfigResolver;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Models\Message;
use App\Modules\ExternalIntegration\Domain\Services\CompanyIntegrationResolver;
use App\Modules\Knowledge\Domain\Services\KnowledgeContextBuilder;
use App\Modules\Lead\Domain\Services\PipelineStageService;

class ConversationContextBuilder
{
    public function __construct(
        private readonly MessageService $messageService,
        private readonly KnowledgeContextBuilder $knowledgeContextBuilder,
        private readonly PipelineStageService $pipelineStageService,
        private readonly CompanyIntegrationResolver $companyIntegrationResolver,
        private readonly CompanyAiConfigResolver $companyAiConfigResolver,
    ) {}

    /**
     * @return list<AiChatMessage>
     */
    public function build(Conversation $conversation): array
    {
        $conversation->loadMissing('lead.pipelineStage');

        $config = new CompanyConfigResolver($conversation->company_id);
        $aiConfig = $this->companyAiConfigResolver->resolve($conversation->company_id);

        $systemPrompt = (string) ($config->get('ai.system_prompt') ?? config('ai.default_system_prompt'));
        $model = $aiConfig->model;

        $currentStage = $conversation->lead->pipelineStage;
        $summary = $conversation->summary ?? 'Sem resumo anterior.';

        $lastCustomerMessage = Message::query()
            ->where('conversation_id', $conversation->id)
            ->where('origin', MessageOrigin::Customer)
            ->orderByDesc('sent_at')
            ->first();

        $knowledgeContext = '';
        if ($lastCustomerMessage !== null) {
            $knowledgeContext = trim($this->knowledgeContextBuilder->build(
                $conversation->company_id,
                $lastCustomerMessage->content,
            ));
        }

        $stagesContext = $this->pipelineStageService->buildAiContextBlock(
            $conversation->company_id,
            $currentStage,
        );

        $systemContent = "{$systemPrompt}\n\n{$stagesContext}\n\nResumo da conversa: {$summary}\nModelo: {$model}";

        if ($this->hasExternalIntegration($conversation->company_id)) {
            $systemContent .= "\n\n".trim((string) config('ai.external_tools_system_prompt'));
        }

        if ($knowledgeContext !== '') {
            $systemContent .= "\n\n--- Base de Conhecimento ---\n{$knowledgeContext}";
        }

        $systemContent .= "\n\n".trim((string) config('ai.response_format_instructions'));

        $messages = [
            new AiChatMessage(
                role: 'system',
                content: $systemContent,
            ),
        ];

        $recentLimit = (int) config('ai.recent_messages_limit');
        $recentMessages = $this->messageService->recentForConversation($conversation, $recentLimit);

        foreach ($this->groupConsecutiveTurns($recentMessages) as $turn) {
            $messages[] = new AiChatMessage(
                role: $turn['role'],
                content: $turn['content'],
            );
        }

        return $messages;
    }

    /**
     * @param  \Illuminate\Support\Collection<int, Message>  $recentMessages
     * @return list<array{role: string, content: string}>
     */
    private function groupConsecutiveTurns($recentMessages): array
    {
        $turns = [];

        foreach ($recentMessages as $message) {
            $role = match ($message->origin) {
                MessageOrigin::Customer => 'user',
                MessageOrigin::Ai => 'assistant',
                MessageOrigin::User => 'assistant',
            };

            $prefix = $message->origin === MessageOrigin::User ? '[Atendente humano] ' : '';
            $content = $prefix.$message->content;

            if ($turns !== [] && $turns[array_key_last($turns)]['role'] === $role) {
                $turns[array_key_last($turns)]['content'] .= "\n".$content;
            } else {
                $turns[] = [
                    'role' => $role,
                    'content' => $content,
                ];
            }
        }

        return $turns;
    }

    private function hasExternalIntegration(int $companyId): bool
    {
        return $this->companyIntegrationResolver->resolve($companyId) !== null;
    }
}
