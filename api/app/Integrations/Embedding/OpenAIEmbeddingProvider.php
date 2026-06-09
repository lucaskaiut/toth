<?php

namespace App\Integrations\Embedding;

use App\Core\Embedding\Contracts\EmbeddingProviderInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenAIEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $apiKey,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $dimensions,
    ) {}

    public function embed(string $text): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('API key de embedding não configurada para a empresa.');
        }

        $url = rtrim($this->baseUrl, '/').'/embeddings';

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post($url, [
                    'model' => $this->model,
                    'input' => $text,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'Falha ao gerar embedding via OpenAI: '.$exception->getMessage(),
                previous: $exception
            );
        }

        $embedding = $response->json('data.0.embedding');

        if (! is_array($embedding)) {
            throw new RuntimeException('Resposta de embedding inválida da API OpenAI.');
        }

        return array_map('floatval', $embedding);
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }
}
