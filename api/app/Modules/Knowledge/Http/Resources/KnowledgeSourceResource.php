<?php

namespace App\Modules\Knowledge\Http\Resources;

use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeSource */
class KnowledgeSourceResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'company_id' => $this->company_id,
            'type' => $this->type->value,
            'title' => $this->title,
            'content' => $this->content,
            'metadata' => $this->metadata,
            'status' => $this->status->value,
            'indexed_at' => $this->indexed_at?->toIso8601String(),
            'index_error' => $this->index_error,
            'created_at' => $this->created_at?->toIso8601String(),
            'updated_at' => $this->updated_at?->toIso8601String(),
        ];
    }
}
