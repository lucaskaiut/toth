<?php

namespace App\Modules\Knowledge\Domain\DTOs;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;

readonly class CreateKnowledgeSourceData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public KnowledgeSourceType $type,
        public string $title,
        public ?string $content = null,
        public ?array $metadata = null,
    ) {}
}
