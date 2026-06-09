<?php

namespace App\Modules\CompanyConfig\Domain\DTOs;

readonly class CompanyAiConfig
{
    public function __construct(
        public string $baseUrl,
        public string $apiKey,
        public string $model,
    ) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->model !== '';
    }
}
