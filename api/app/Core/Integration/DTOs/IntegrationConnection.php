<?php

namespace App\Core\Integration\DTOs;

use App\Core\Integration\Enums\ExternalIntegrationProvider;

readonly class IntegrationConnection
{
    public function __construct(
        public ExternalIntegrationProvider $provider,
        public string $apiToken,
        public int $companyId,
    ) {}
}
