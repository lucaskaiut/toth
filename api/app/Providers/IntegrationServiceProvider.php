<?php

namespace App\Providers;

use App\Core\AI\Contracts\AiClient;
use App\Core\Embedding\Contracts\EmbeddingProviderInterface;
use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Integrations\AI\OpenAICompatible\OpenAiCompatibleClient;
use App\Integrations\ExternalTools\HttpExternalToolClient;
use App\Integrations\Embedding\LocalEmbeddingProvider;
use App\Integrations\Embedding\OpenAIEmbeddingProvider;
use App\Integrations\Whatsapp\Evolution\EvolutionWebhookParser;
use App\Integrations\Whatsapp\Evolution\EvolutionWhatsAppClient;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class IntegrationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(WhatsAppClient::class, function ($app) {
            $driver = config('whatsapp.driver');

            return match ($driver) {
                'evolution' => new EvolutionWhatsAppClient(
                    baseUrl: (string) config('whatsapp.base_url'),
                    apiKey: (string) config('whatsapp.api_key'),
                    timeout: (int) config('whatsapp.timeout'),
                    webhookParser: $app->make(EvolutionWebhookParser::class),
                    integrationLogService: $app->make(\App\Modules\IntegrationLog\Domain\Services\IntegrationLogService::class),
                ),
                default => throw new InvalidArgumentException("Driver WhatsApp não suportado: {$driver}"),
            };
        });

        $this->app->singleton(AiClient::class, function ($app) {
            $driver = config('ai.driver');

            return match ($driver) {
                'openai_compatible' => new OpenAiCompatibleClient(
                    timeout: (int) config('ai.timeout'),
                    integrationLogService: $app->make(\App\Modules\IntegrationLog\Domain\Services\IntegrationLogService::class),
                ),
                default => throw new InvalidArgumentException("Driver de IA não suportado: {$driver}"),
            };
        });

        $this->app->singleton(EmbeddingProviderInterface::class, function () {
            $driver = config('embedding.driver');
            $dimensions = (int) config('embedding.dimensions', 768);

            return match ($driver) {
                'ollama' => new LocalEmbeddingProvider(
                    baseUrl: (string) config('embedding.ollama.url'),
                    model: (string) config('embedding.ollama.model'),
                    timeout: (int) config('embedding.ollama.timeout'),
                    dimensions: $dimensions,
                ),
                'openai' => new OpenAIEmbeddingProvider(
                    baseUrl: (string) config('embedding.openai.base_url'),
                    apiKey: (string) config('embedding.openai.api_key', ''),
                    model: (string) config('embedding.openai.model'),
                    timeout: (int) config('embedding.openai.timeout'),
                    dimensions: $dimensions,
                ),
                default => throw new InvalidArgumentException("Driver de embedding não suportado: {$driver}"),
            };
        });

        $this->app->singleton(ExternalToolClient::class, function ($app) {
            return new HttpExternalToolClient(
                integrationLogService: $app->make(\App\Modules\IntegrationLog\Domain\Services\IntegrationLogService::class),
            );
        });
    }
}
