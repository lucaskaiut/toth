<?php

namespace App\Modules\Realtime\Events;

use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class LeadStageChanged implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly Lead $lead,
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
        return 'lead.stage_changed';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $this->lead->loadMissing('pipelineStage');

        return [
            'lead' => [
                'id' => $this->lead->id,
                'pipeline_stage_id' => $this->lead->pipeline_stage_id,
                'pipeline_stage' => [
                    'id' => $this->lead->pipelineStage->id,
                    'name' => $this->lead->pipelineStage->name,
                    'slug' => $this->lead->pipelineStage->slug,
                ],
            ],
        ];
    }
}
