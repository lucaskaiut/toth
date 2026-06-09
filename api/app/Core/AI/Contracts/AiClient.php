<?php

namespace App\Core\AI\Contracts;

use App\Core\AI\DTOs\AiChatRequest;
use App\Core\AI\DTOs\AiCompletionResponse;
use App\Core\AI\DTOs\AiStructuredResponse;

interface AiClient
{
    public function chat(AiChatRequest $request): AiStructuredResponse;

    public function completion(AiChatRequest $request): AiCompletionResponse;
}
