<?php

namespace App\Modules\Knowledge\Domain\DTOs;

readonly class KnowledgeSearchResult
{
    /**
     * @param  array<string, mixed>|null  $sourceMetadata
     * @param  array<string, mixed>|null  $chunkMetadata
     */
    public function __construct(
        public int $chunkId,
        public int $sourceId,
        public string $sourceType,
        public string $sourceTitle,
        public int $chunkIndex,
        public string $content,
        public float $score,
        public ?array $sourceMetadata = null,
        public ?array $chunkMetadata = null,
    ) {}
}
