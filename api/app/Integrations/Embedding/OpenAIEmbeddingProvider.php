<?php

namespace App\Integrations\Embedding;

use App\Core\Embedding\Contracts\EmbeddingProviderInterface;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
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
        private readonly ?IntegrationLogService $integrationLogService = null,
        private readonly ?int $companyId = null,
    ) {}

    public function embed(string $text): array
    {
        if ($this->apiKey === '') {
            throw new RuntimeException('API key de embedding não configurada para a empresa.');
        }

        $url = rtrim($this->baseUrl, '/').'/embeddings';

        $payload = [
            'model' => $this->model,
            'input' => $text,
        ];

        if ($this->dimensions > 0) {
            $payload['dimensions'] = $this->dimensions;
        }

        $this->log('info', 'embedding_request', 'Embedding request', [
            'url' => $url,
            'model' => $this->model,
            'dimensions' => $this->dimensions > 0 ? $this->dimensions : null,
        ]);

        try {
            $response = Http::timeout($this->timeout)
                ->withToken($this->apiKey)
                ->post($url, $payload)
                ->throw();
        } catch (RequestException $exception) {
            $this->log('error', 'embedding_request', 'Falha ao gerar embedding: '.$exception->getMessage(), [
                'url' => $url,
                'model' => $this->model,
            ]);

            throw new RuntimeException(
                'Falha ao gerar embedding via OpenAI: '.$exception->getMessage(),
                previous: $exception
            );
        }

        $embedding = $response->json('data.0.embedding');

        if (! is_array($embedding)) {
            throw new RuntimeException('Resposta de embedding inválida da API OpenAI.');
        }

        $vector = array_map('floatval', $embedding);

        if ($this->dimensions > 0 && count($vector) !== $this->dimensions) {
            $message = sprintf(
                'Embedding retornou %d dimensões, mas o sistema espera %d. Verifique embedding.dimensions e o modelo configurado.',
                count($vector),
                $this->dimensions,
            );

            $this->log('error', 'embedding_dimensions', $message, [
                'model' => $this->model,
                'expected_dimensions' => $this->dimensions,
                'received_dimensions' => count($vector),
            ]);

            throw new RuntimeException($message);
        }

        $this->log('info', 'embedding_response', 'Embedding response OK', [
            'model' => $this->model,
            'dimensions' => count($vector),
        ]);

        return $vector;
    }

    public function dimensions(): int
    {
        return $this->dimensions;
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function log(string $level, string $action, string $message, array $context = []): void
    {
        if ($this->integrationLogService === null) {
            return;
        }

        if ($level === 'error') {
            $this->integrationLogService->error(
                integration: 'embedding',
                action: $action,
                message: $message,
                context: $context,
                companyId: $this->companyId,
            );

            return;
        }

        $this->integrationLogService->info(
            integration: 'embedding',
            action: $action,
            message: $message,
            context: $context,
            companyId: $this->companyId,
        );
    }
}
