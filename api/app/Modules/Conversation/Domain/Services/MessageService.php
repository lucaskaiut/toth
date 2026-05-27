<?php

namespace App\Modules\Conversation\Domain\Services;

use App\Models\User;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Models\Message;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;

class MessageService
{
    public function __construct(
        private readonly ConversationService $conversationService,
    ) {}

    /**
     * @return Collection<int, Message>
     */
    public function forConversation(Conversation $conversation): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->with('user')
            ->orderBy('sent_at')
            ->get();
    }

    public function store(
        Conversation $conversation,
        MessageOrigin $origin,
        string $content,
        ?User $user = null,
        ?Carbon $sentAt = null,
    ): Message {
        $message = Message::query()->create([
            'conversation_id' => $conversation->id,
            'origin' => $origin,
            'user_id' => $user?->id,
            'content' => $content,
            'sent_at' => $sentAt ?? now(),
        ]);

        $this->conversationService->touch($conversation);

        return $message->load('user');
    }

    /**
     * @return Collection<int, Message>
     */
    public function recentForConversation(Conversation $conversation, int $limit): Collection
    {
        return Message::query()
            ->where('conversation_id', $conversation->id)
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get()
            ->reverse()
            ->values();
    }
}
