<?php

namespace App\Modules\Realtime\Events;

use App\Modules\Conversation\Domain\Models\Message;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class MessageCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly Message $message,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'message.created';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->message->loadMissing('user');

        return [
            'message' => [
                'id' => $this->message->id,
                'conversation_id' => $this->message->conversation_id,
                'origin' => $this->message->origin->value,
                'content' => $this->message->content,
                'sent_at' => $this->message->sent_at?->toIso8601String(),
                'user' => $this->message->user ? [
                    'id' => $this->message->user->id,
                    'name' => $this->message->user->name,
                ] : null,
            ],
        ];
    }
}
