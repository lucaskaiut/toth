<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Integrations\Embedding\OpenAIEmbeddingProvider;
use App\Modules\CompanyConfig\Domain\Services\CompanyAiConfigResolver;
use RuntimeException;

class EmbeddingService
{
    public function __construct(
        private readonly CompanyAiConfigResolver $companyAiConfigResolver,
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

        return $this->makeProvider($config->baseUrl, $config->apiKey, $config->embeddingModel)->embed($text);
    }

    public function dimensions(): int
    {
        return (int) config('embedding.dimensions', 768);
    }

    private function makeProvider(string $baseUrl, string $apiKey, string $model): OpenAIEmbeddingProvider
    {
        return new OpenAIEmbeddingProvider(
            baseUrl: $baseUrl,
            apiKey: $apiKey,
            model: $model,
            timeout: (int) config('embedding.openai.timeout', 120),
            dimensions: $this->dimensions(),
        );
    }
}
