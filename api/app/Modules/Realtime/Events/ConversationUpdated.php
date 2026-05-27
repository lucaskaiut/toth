<?php

namespace App\Modules\Realtime\Events;

use App\Modules\Conversation\Domain\Models\Conversation;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ConversationUpdated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly Conversation $conversation,
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
        return 'conversation.updated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->conversation->loadMissing('lead.pipelineStage');

        return [
            'conversation' => [
                'id' => $this->conversation->id,
                'lead_id' => $this->conversation->lead_id,
                'summary' => $this->conversation->summary,
                'attendance_status' => $this->conversation->attendance_status->value,
                'attendance_status_label' => $this->conversation->attendance_status->label(),
                'updated_at' => $this->conversation->updated_at?->toIso8601String(),
                'lead' => [
                    'id' => $this->conversation->lead->id,
                    'name' => $this->conversation->lead->name,
                    'phone' => $this->conversation->lead->phone,
                    'pipeline_stage_id' => $this->conversation->lead->pipeline_stage_id,
                ],
            ],
        ];
    }
}
