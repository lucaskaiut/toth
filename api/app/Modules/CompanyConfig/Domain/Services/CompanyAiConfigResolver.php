<?php

namespace App\Modules\CompanyConfig\Domain\Services;

use App\Modules\CompanyConfig\Domain\DTOs\CompanyAiConfig;

class CompanyAiConfigResolver
{
    public function resolve(int $companyId): CompanyAiConfig
    {
        $config = new CompanyConfigResolver($companyId);

        return new CompanyAiConfig(
            baseUrl: (string) ($config->get('ai.base_url') ?? config('ai.default_base_url')),
            apiKey: (string) $config->get('ai.api_key', ''),
            model: (string) ($config->get('ai.model') ?? config('ai.default_model')),
        );
    }
}
