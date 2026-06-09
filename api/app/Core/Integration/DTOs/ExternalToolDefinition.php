<?php

namespace App\Core\Integration\DTOs;

readonly class ExternalToolDefinition
{
    /**
     * @param  list<array{name: string, description: string, type: string, required: bool}>  $parameters
     */
    public function __construct(
        public string $name,
        public string $description,
        public array $parameters,
    ) {}
}
