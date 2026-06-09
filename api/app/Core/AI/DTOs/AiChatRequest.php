<?php

namespace App\Core\AI\DTOs;

readonly class AiChatRequest
{
    /**
     * @param  list<AiChatMessage>  $messages
     * @param  list<array<string, mixed>>|null  $tools
     */
    public function __construct(
        public string $model,
        public string $apiKey,
        public array $messages,
        public ?string $responseFormat = 'json_object',
        public ?array $tools = null,
        public ?int $companyId = null,
    ) {}
}
