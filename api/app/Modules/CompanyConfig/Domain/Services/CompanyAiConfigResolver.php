<?php

namespace App\Modules\CompanyConfig\Domain\Services;

use App\Modules\CompanyConfig\Domain\DTOs\CompanyAiConfig;

class CompanyAiConfigResolver
{
    public function resolve(int $companyId): CompanyAiConfig
    {
        $config = new CompanyConfigResolver($companyId);

        $baseUrl = (string) ($config->get('ai.base_url') ?? config('ai.default_base_url'));
        $apiKey = (string) $config->get('ai.api_key', '');

        return new CompanyAiConfig(
            baseUrl: $baseUrl,
            apiKey: $apiKey,
            model: (string) ($config->get('ai.model') ?? config('ai.default_model')),
            embeddingModel: (string) (
                $config->get('embedding.model')
                ?? config('embedding.openai.model')
            ),
            embeddingDimensions: (int) (
                $config->get('embedding.dimensions')
                ?? config('embedding.dimensions', 768)
            ),
        );
    }
}
