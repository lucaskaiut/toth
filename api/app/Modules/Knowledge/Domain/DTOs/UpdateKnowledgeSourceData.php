<?php

namespace App\Modules\Knowledge\Domain\DTOs;

readonly class UpdateKnowledgeSourceData
{
    /**
     * @param  array<string, mixed>|null  $metadata
     */
    public function __construct(
        public ?string $title = null,
        public ?string $content = null,
        public ?array $metadata = null,
    ) {}
}
