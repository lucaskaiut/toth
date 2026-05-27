<?php

namespace App\Modules\Lead\Http\Resources;

use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Lead */
class LeadResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'phone' => $this->phone,
            'email' => $this->email,
            'company_name' => $this->company_name,
            'notes' => $this->notes,
            'pipeline_stage_id' => $this->pipeline_stage_id,
            'pipeline_stage' => $this->whenLoaded('pipelineStage', fn () => [
                'id' => $this->pipelineStage->id,
                'name' => $this->pipelineStage->name,
                'slug' => $this->pipelineStage->slug,
                'position' => $this->pipelineStage->position,
            ]),
            'conversation_id' => $this->whenLoaded('conversation', fn () => $this->conversation?->id),
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
