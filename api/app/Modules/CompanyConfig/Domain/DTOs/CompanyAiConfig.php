<?php

namespace App\Modules\CompanyConfig\Domain\DTOs;

readonly class CompanyAiConfig
{
    public function __construct(
        public string $baseUrl,
        public string $apiKey,
        public string $model,
        public string $embeddingModel,
    ) {}

    public function isConfigured(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->model !== '';
    }

    public function hasEmbeddingCredentials(): bool
    {
        return $this->baseUrl !== '' && $this->apiKey !== '' && $this->embeddingModel !== '';
    }
}
