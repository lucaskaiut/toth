<?php

namespace App\Modules\CompanyConfig\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigService;
use App\Modules\CompanyConfig\Http\Requests\UpdateCompanyConfigsRequest;
use App\Modules\CompanyConfig\Http\Resources\CompanyConfigResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class CompanyConfigController extends Controller
{
    public function __construct(
        private readonly CompanyConfigService $companyConfigService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CompanyConfigResource::collection($this->companyConfigService->all());
    }

    public function update(UpdateCompanyConfigsRequest $request): AnonymousResourceCollection
    {
        $payload = [];

        foreach ($request->validated('configs') as $config) {
            $payload[$config['key']] = [
                'value' => $config['value'] ?? null,
                'type' => $config['type'] ?? 'string',
            ];
        }

        $this->companyConfigService->setMany($payload);

        return CompanyConfigResource::collection($this->companyConfigService->all());
    }
}
