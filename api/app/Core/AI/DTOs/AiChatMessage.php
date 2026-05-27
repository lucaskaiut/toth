<?php

namespace App\Core\AI\DTOs;

readonly class AiChatMessage
{
    public function __construct(
        public string $role,
        public string $content,
    ) {}
}
