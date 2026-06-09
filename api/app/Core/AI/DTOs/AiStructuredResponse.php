<?php

namespace App\Core\AI\DTOs;

readonly class AiStructuredResponse
{
    public function __construct(
        public string $message,
        public ?string $suggestedStage,
        public string $summary,
        public bool $shouldReply = true,
        public bool $requiresHandoff = false,
        public bool $isGenericFallback = false,
        public ?string $parseNote = null,
    ) {}
}
