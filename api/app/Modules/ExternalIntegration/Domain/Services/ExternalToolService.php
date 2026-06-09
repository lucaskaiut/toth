<?php

namespace App\Modules\ExternalIntegration\Domain\Services;

use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use App\Core\Integration\DTOs\IntegrationConnection;

class ExternalToolService
{
    public function __construct(
        private readonly ExternalToolClient $externalToolClient,
        private readonly CompanyIntegrationResolver $companyIntegrationResolver,
    ) {}

    public function connectionForCompany(int $companyId): ?IntegrationConnection
    {
        return $this->companyIntegrationResolver->resolve($companyId);
    }

    /**
     * @return list<ExternalToolDefinition>
     */
    public function discoverTools(int $companyId): array
    {
        $connection = $this->connectionForCompany($companyId);

        if ($connection === null) {
            return [];
        }

        return $this->externalToolClient->discoverTools($connection);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function toOpenAiTools(int $companyId): array
    {
        try {
            return array_map(
                fn (ExternalToolDefinition $tool) => $this->toOpenAiTool($tool),
                $this->discoverTools($companyId),
            );
        } catch (\Throwable) {
            return [];
        }
    }

    /**
     * @param  list<string>|null  $allowedToolNames
     * @param  array<string, mixed>  $parameters
     */
    public function execute(
        int $companyId,
        string $toolName,
        array $parameters,
        ?array $allowedToolNames = null,
    ): ExternalToolExecutionResult {
        $connection = $this->connectionForCompany($companyId);

        if ($connection === null) {
            return new ExternalToolExecutionResult(
                success: false,
                error: ['message' => 'Integração externa não configurada.'],
            );
        }

        if ($allowedToolNames !== null && ! in_array($toolName, $allowedToolNames, true)) {
            return new ExternalToolExecutionResult(
                success: false,
                error: ['message' => 'Ferramenta não disponível.'],
            );
        }

        return $this->externalToolClient->executeTool($connection, $toolName, $parameters);
    }

    /**
     * @return array<string, mixed>
     */
    private function toOpenAiTool(ExternalToolDefinition $tool): array
    {
        $properties = [];
        $required = [];

        foreach ($tool->parameters as $parameter) {
            $name = (string) ($parameter['name'] ?? '');

            if ($name === '') {
                continue;
            }

            $properties[$name] = array_filter([
                'type' => $this->mapParameterType((string) ($parameter['type'] ?? 'string')),
                'description' => (string) ($parameter['description'] ?? ''),
            ]);

            if (($parameter['required'] ?? false) === true) {
                $required[] = $name;
            }
        }

        return [
            'type' => 'function',
            'function' => [
                'name' => $tool->name,
                'description' => $tool->description,
                'parameters' => [
                    'type' => 'object',
                    'properties' => $properties,
                    'required' => $required,
                ],
            ],
        ];
    }

    private function mapParameterType(string $type): string
    {
        return match ($type) {
            'integer', 'int' => 'integer',
            'number', 'float' => 'number',
            'boolean', 'bool' => 'boolean',
            default => 'string',
        };
    }
}
