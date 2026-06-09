<?php

namespace App\Modules\ExternalIntegration\Domain\Services;

use App\Core\Integration\DTOs\IntegrationConnection;
use App\Core\Integration\Enums\ExternalIntegrationProvider;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;

class CompanyIntegrationResolver
{
    public function resolve(int $companyId): ?IntegrationConnection
    {
        $config = new CompanyConfigResolver($companyId);

        if (! (bool) $config->get('integration.enabled', false)) {
            return null;
        }

        $providerValue = (string) $config->get('integration.provider', '');

        if ($providerValue === '') {
            return null;
        }

        $provider = ExternalIntegrationProvider::tryFrom($providerValue);

        if ($provider === null) {
            return null;
        }

        $apiToken = trim((string) $config->get('integration.api_token', ''));

        if ($apiToken === '') {
            return null;
        }

        if ($provider->baseUrl() === '') {
            return null;
        }

        return new IntegrationConnection(
            provider: $provider,
            apiToken: $apiToken,
            companyId: $companyId,
        );
    }
}
