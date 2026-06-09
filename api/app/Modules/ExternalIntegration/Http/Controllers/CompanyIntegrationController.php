<?php

namespace App\Modules\ExternalIntegration\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\ExternalIntegration\Domain\Services\CompanyIntegrationResolver;
use App\Modules\ExternalIntegration\Domain\Services\ExternalToolService;
use Illuminate\Http\JsonResponse;

class CompanyIntegrationController extends Controller
{
    public function __construct(
        private readonly CompanyIntegrationResolver $companyIntegrationResolver,
        private readonly ExternalToolService $externalToolService,
    ) {}

    public function status(): JsonResponse
    {
        $companyId = app(\App\Modules\Company\Domain\CurrentCompany::class)->id();
        $connection = $this->companyIntegrationResolver->resolve($companyId);

        if ($connection === null) {
            return response()->json([
                'configured' => false,
                'tools_count' => 0,
                'tools' => [],
                'message' => 'Integração externa não configurada ou incompleta (provider/token).',
            ]);
        }

        $tools = $this->externalToolService->discoverTools($companyId);

        return response()->json([
            'configured' => true,
            'provider' => $connection->provider->value,
            'tools_count' => count($tools),
            'tools' => array_map(
                fn ($tool) => [
                    'name' => $tool->name,
                    'description' => $tool->description,
                    'parameters' => $tool->parameters,
                ],
                $tools,
            ),
        ]);
    }
}
