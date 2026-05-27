<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Core\AI\DTOs\AiChatMessage;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;

class ConversationContextBuilder
{
    public function __construct(
        private readonly MessageService $messageService,
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

        $stageName = $conversation->lead->pipelineStage->name;
        $summary = $conversation->summary ?? 'Sem resumo anterior.';

        $messages = [
            new AiChatMessage(
                role: 'system',
                content: "{$systemPrompt}\n\nEstágio atual do lead: {$stageName}.\nResumo da conversa: {$summary}\nModelo: {$model}",
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
}
