<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Integrations\Embedding\OpenAIEmbeddingProvider;
use App\Modules\CompanyConfig\Domain\Services\CompanyAiConfigResolver;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use RuntimeException;

class EmbeddingService
{
    public function __construct(
        private readonly CompanyAiConfigResolver $companyAiConfigResolver,
        private readonly IntegrationLogService $integrationLogService,
    ) {}

    /**
     * @return list<float>
     */
    public function embedForCompany(int $companyId, string $text): array
    {
        $config = $this->companyAiConfigResolver->resolve($companyId);

        if (! $config->hasEmbeddingCredentials()) {
            throw new RuntimeException('Configuração de embedding incompleta para a empresa.');
        }

        return $this->makeProvider(
            companyId: $companyId,
            baseUrl: $config->baseUrl,
            apiKey: $config->apiKey,
            model: $config->embeddingModel,
            dimensions: $config->embeddingDimensions,
        )->embed($text);
    }

    public function dimensionsForCompany(int $companyId): int
    {
        return $this->companyAiConfigResolver->resolve($companyId)->embeddingDimensions;
    }

    private function makeProvider(
        int $companyId,
        string $baseUrl,
        string $apiKey,
        string $model,
        int $dimensions,
    ): OpenAIEmbeddingProvider {
        return new OpenAIEmbeddingProvider(
            baseUrl: $baseUrl,
            apiKey: $apiKey,
            model: $model,
            timeout: (int) config('embedding.openai.timeout', 120),
            dimensions: $dimensions,
            integrationLogService: $this->integrationLogService,
            companyId: $companyId,
        );
    }
}
