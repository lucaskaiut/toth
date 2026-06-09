<?php

namespace App\Providers;

use App\Core\AI\Contracts\AiClient;
use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Whatsapp\Contracts\WhatsAppClient;
use App\Integrations\AI\OpenAICompatible\OpenAiCompatibleClient;
use App\Integrations\ExternalTools\HttpExternalToolClient;
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

        $this->app->singleton(ExternalToolClient::class, function ($app) {
            return new HttpExternalToolClient(
                integrationLogService: $app->make(\App\Modules\IntegrationLog\Domain\Services\IntegrationLogService::class),
            );
        });
    }
}
