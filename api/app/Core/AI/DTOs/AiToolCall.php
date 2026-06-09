<?php

namespace App\Core\AI\DTOs;

readonly class AiToolCall
{
    public function __construct(
        public string $id,
        public string $name,
        public string $arguments,
    ) {}
}
