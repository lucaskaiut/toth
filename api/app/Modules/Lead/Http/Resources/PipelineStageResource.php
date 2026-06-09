<?php

namespace App\Modules\Lead\Http\Resources;

use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin PipelineStage */
class PipelineStageResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'position' => $this->position,
            'description' => $this->description,
            'ai_instruction' => $this->ai_instruction,
        ];
    }
}
