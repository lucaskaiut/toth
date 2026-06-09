<?php

namespace App\Core\AI\DTOs;

readonly class AiCompletionResponse
{
    /**
     * @param  list<AiToolCall>  $toolCalls
     */
    public function __construct(
        public string $content,
        public array $toolCalls = [],
    ) {}

    public function hasToolCalls(): bool
    {
        return $this->toolCalls !== [];
    }
}
