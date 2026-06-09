<?php

namespace App\Modules\Knowledge\Http\Resources;

use App\Modules\Knowledge\Domain\DTOs\KnowledgeSearchResult;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/** @mixin KnowledgeSearchResult */
class KnowledgeSearchResultResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'chunk_id' => $this->chunkId,
            'source_id' => $this->sourceId,
            'source_type' => $this->sourceType,
            'source_title' => $this->sourceTitle,
            'chunk_index' => $this->chunkIndex,
            'content' => $this->content,
            'score' => $this->score,
            'source_metadata' => $this->sourceMetadata,
            'chunk_metadata' => $this->chunkMetadata,
        ];
    }
}
