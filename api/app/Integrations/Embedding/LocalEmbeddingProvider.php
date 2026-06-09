<?php

namespace App\Integrations\Embedding;

use App\Core\Embedding\Contracts\EmbeddingProviderInterface;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class LocalEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $model,
        private readonly int $timeout,
        private readonly int $dimensions,
    ) {}

    public function embed(string $text): array
    {
        $url = rtrim($this->baseUrl, '/').'/api/embeddings';

        try {
            $response = Http::timeout($this->timeout)
                ->post($url, [
                    'model' => $this->model,
                    'input' => $text,
                ])
                ->throw();
        } catch (RequestException $exception) {
            throw new RuntimeException(
                'Falha ao gerar embedding via Ollama: '.$exception->getMessage(),
                previous: $exception
            );
        }

        $embedding = $response->json('embedding');

        if (! is_array($embedding) && is_array($response->json('data.0.embedding'))) {
            $embedding = $response->json('data.0.embedding');
        }

        if (! is_array($embedding)) {
            $embeddings = $response->json('embeddings.0');
            if (is_array($embeddings)) {
                $embedding = $embeddings;
            }
        }

        if (! is_array($embedding)) {
            throw new RuntimeException('Resposta de embedding inválida do Ollama.');
        }

        return $this->normalizeVector(array_map('floatval', $embedding));
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * @param  list<float>  $vector
     * @return list<float>
     */
    private function normalizeVector(array $vector): array
    {
        if (count($vector) === $this->dimensions) {
            return $vector;
        }

        if (count($vector) > $this->dimensions) {
            return array_slice($vector, 0, $this->dimensions);
        }

        return array_pad($vector, $this->dimensions, 0.0);
    }
}
