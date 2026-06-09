<?php

namespace App\Core\Integration\Contracts;

use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use App\Core\Integration\DTOs\IntegrationConnection;

interface ExternalToolClient
{
    /**
     * @return list<ExternalToolDefinition>
     */
    public function discoverTools(IntegrationConnection $connection): array;

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function executeTool(
        IntegrationConnection $connection,
        string $toolName,
        array $parameters,
    ): ExternalToolExecutionResult;
}
