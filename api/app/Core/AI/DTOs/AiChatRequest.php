<?php

namespace App\Core\AI\DTOs;

readonly class AiChatRequest
{
    /**
     * @param  list<AiChatMessage>  $messages
     */
    public function __construct(
        public string $model,
        public string $apiKey,
        public array $messages,
        public ?string $responseFormat = 'json_object',
    ) {}
}
