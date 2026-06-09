<?php

namespace App\Core\AI\DTOs;

readonly class AiChatMessage
{
    /**
     * @param  list<AiToolCall>|null  $toolCalls
     */
    public function __construct(
        public string $role,
        public string $content = '',
        public ?string $toolCallId = null,
        public ?array $toolCalls = null,
    ) {}
}
