<?php

namespace App\Modules\Conversation\Http\Resources;

use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Http\Resources\LeadResource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin Conversation */
class ConversationResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'lead_id' => $this->lead_id,
            'summary' => $this->summary,
            'attendance_status' => $this->attendance_status->value,
            'attendance_status_label' => $this->attendance_status->label(),
            'lead' => new LeadResource($this->whenLoaded('lead')),
            'updated_at' => $this->updated_at?->toIso8601String(),
            'created_at' => $this->created_at?->toIso8601String(),
        ];
    }
}
