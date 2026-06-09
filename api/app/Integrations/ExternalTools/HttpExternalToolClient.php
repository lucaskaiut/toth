<?php

namespace App\Integrations\ExternalTools;

use App\Core\Integration\Contracts\ExternalToolClient;
use App\Core\Integration\DTOs\ExternalToolDefinition;
use App\Core\Integration\DTOs\ExternalToolExecutionResult;
use App\Core\Integration\DTOs\IntegrationConnection;
use App\Modules\IntegrationLog\Domain\Services\IntegrationLogService;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use InvalidArgumentException;

class HttpExternalToolClient implements ExternalToolClient
{
    public function __construct(
        private readonly IntegrationLogService $integrationLogService,
    ) {}

    /**
     * @return list<ExternalToolDefinition>
     */
    public function discoverTools(IntegrationConnection $connection): array
    {
        $url = $this->toolsUrl($connection);

        $this->integrationLogService->info(
            integration: $this->integrationName($connection),
            action: 'discover_tools',
            message: 'Consultando ferramentas disponíveis',
            context: ['url' => $url],
            companyId: $connection->companyId,
        );

        try {
            $response = $this->http($connection)->get($url);
            $this->logResponse($connection, 'discover_tools', $url, $response);

            if (! $response->successful()) {
                throw new InvalidArgumentException('Não foi possível obter ferramentas do sistema externo.');
            }

            $items = $response->json('data');

            if (! is_array($items)) {
                throw new InvalidArgumentException('Resposta de ferramentas inválida.');
            }

            $tools = [];

            foreach ($items as $item) {
                if (! is_array($item) || ! isset($item['name'], $item['description'], $item['parameters'])) {
                    continue;
                }

                $parameters = is_array($item['parameters']) ? $item['parameters'] : [];

                $tools[] = new ExternalToolDefinition(
                    name: (string) $item['name'],
                    description: (string) $item['description'],
                    parameters: array_values(array_filter($parameters, 'is_array')),
                );
            }

            $this->integrationLogService->info(
                integration: $this->integrationName($connection),
                action: 'discover_tools',
                message: 'Ferramentas descobertas',
                context: [
                    'count' => count($tools),
                    'names' => array_map(fn (ExternalToolDefinition $tool) => $tool->name, $tools),
                ],
                companyId: $connection->companyId,
            );

            return $tools;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: $this->integrationName($connection),
                action: 'discover_tools',
                message: $exception->getMessage(),
                companyId: $connection->companyId,
            );

            throw new InvalidArgumentException('Erro ao consultar ferramentas externas.', 0, $exception);
        }
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function executeTool(
        IntegrationConnection $connection,
        string $toolName,
        array $parameters,
    ): ExternalToolExecutionResult {
        $url = $this->executeUrl($connection);
        $payload = [
            'tool' => $toolName,
            'parameters' => $parameters,
        ];

        $this->integrationLogService->info(
            integration: $this->integrationName($connection),
            action: 'execute_tool',
            message: 'Executando ferramenta externa',
            context: [
                'url' => $url,
                'tool' => $toolName,
                'parameters' => $parameters,
            ],
            companyId: $connection->companyId,
        );

        try {
            $response = $this->http($connection)->post($url, $payload);
            $this->logResponse($connection, 'execute_tool', $url, $response, $toolName);

            $body = $response->json();

            if (! is_array($body)) {
                return new ExternalToolExecutionResult(
                    success: false,
                    error: ['message' => 'Resposta inválida do sistema externo.'],
                );
            }

            if (($body['success'] ?? false) === true) {
                $data = is_array($body['data'] ?? null) ? $body['data'] : [];

                $this->integrationLogService->info(
                    integration: $this->integrationName($connection),
                    action: 'execute_tool',
                    message: 'Ferramenta executada com sucesso',
                    context: ['tool' => $toolName, 'data' => $data],
                    companyId: $connection->companyId,
                );

                return new ExternalToolExecutionResult(success: true, data: $data);
            }

            $error = is_array($body['error'] ?? null) ? $body['error'] : ['message' => 'Falha na execução da ferramenta.'];

            $this->integrationLogService->error(
                integration: $this->integrationName($connection),
                action: 'execute_tool',
                message: (string) ($error['message'] ?? 'Falha na execução da ferramenta.'),
                context: ['tool' => $toolName, 'error' => $error, 'status' => $response->status()],
                companyId: $connection->companyId,
            );

            return new ExternalToolExecutionResult(success: false, error: $error);
        } catch (\Throwable $exception) {
            $this->integrationLogService->error(
                integration: $this->integrationName($connection),
                action: 'execute_tool',
                message: $exception->getMessage(),
                context: ['tool' => $toolName],
                companyId: $connection->companyId,
            );

            return new ExternalToolExecutionResult(
                success: false,
                error: ['message' => 'Erro ao executar ferramenta externa.'],
            );
        }
    }

    private function http(IntegrationConnection $connection): \Illuminate\Http\Client\PendingRequest
    {
        return Http::timeout($connection->provider->timeout())
            ->withToken($connection->apiToken)
            ->withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
            ])
            ->acceptJson();
    }

    private function toolsUrl(IntegrationConnection $connection): string
    {
        return $connection->provider->baseUrl().'/api/integration/tools';
    }

    private function executeUrl(IntegrationConnection $connection): string
    {
        return $connection->provider->baseUrl().'/api/integration/tools/execute';
    }

    private function integrationName(IntegrationConnection $connection): string
    {
        return 'external:'.$connection->provider->value;
    }

    private function logResponse(
        IntegrationConnection $connection,
        string $action,
        string $url,
        Response $response,
        ?string $toolName = null,
    ): void {
        $context = [
            'url' => $url,
            'status' => $response->status(),
            'body' => $response->json() ?? $response->body(),
        ];

        if ($toolName !== null) {
            $context['tool'] = $toolName;
        }

        if ($response->successful()) {
            $this->integrationLogService->info(
                integration: $this->integrationName($connection),
                action: $action,
                message: 'Resposta recebida',
                context: $context,
                companyId: $connection->companyId,
            );

            return;
        }

        $this->integrationLogService->error(
            integration: $this->integrationName($connection),
            action: $action,
            message: 'Erro na resposta externa',
            context: $context,
            companyId: $connection->companyId,
        );
    }
}
