<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\DTOs\AiChatMessage;
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
    ) {}

    /**
     * @return list<AiChatMessage>
     */
    public function build(Conversation $conversation): array
    {
        $conversation->loadMissing('lead.pipelineStage');

        $config = new CompanyConfigResolver($conversation->company_id);

        $systemPrompt = (string) ($config->get('ai.system_prompt') ?? config('ai.default_system_prompt'));
        $model = (string) ($config->get('ai.model') ?? config('ai.default_model'));

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

        $messages = [
            new AiChatMessage(
                role: 'system',
                content: $systemContent,
            ),
        ];

        $recentLimit = (int) config('ai.recent_messages_limit');
        $recentMessages = $this->messageService->recentForConversation($conversation, $recentLimit);

        foreach ($recentMessages as $message) {
            $role = match ($message->origin) {
                MessageOrigin::Customer => 'user',
                MessageOrigin::Ai => 'assistant',
                MessageOrigin::User => 'assistant',
            };

            $prefix = $message->origin === MessageOrigin::User ? '[Atendente humano] ' : '';

            $messages[] = new AiChatMessage(
                role: $role,
                content: $prefix.$message->content,
            );
        }

        return $messages;
    }

    private function hasExternalIntegration(int $companyId): bool
    {
        return $this->companyIntegrationResolver->resolve($companyId) !== null;
    }
}
