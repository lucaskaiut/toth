<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\DTOs\CreateCompanyData;
use App\Modules\Company\Domain\DTOs\UpdateCompanyData;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Company\Domain\Services\CompanyService;
use App\Modules\Company\Http\Requests\StoreCompanyRequest;
use App\Modules\Company\Http\Requests\UpdateCompanyRequest;
use App\Modules\Company\Http\Resources\CompanyResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class CompanyController extends Controller
{
    public function __construct(
        private readonly CompanyService $companyService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return CompanyResource::collection($this->companyService->all());
    }

    public function store(StoreCompanyRequest $request): CompanyResource
    {
        $company = $this->companyService->create(
            new CreateCompanyData(name: $request->validated('name')),
        );

        return new CompanyResource($company);
    }

    public function show(Company $company): CompanyResource
    {
        return new CompanyResource($company);
    }

    public function update(UpdateCompanyRequest $request, Company $company): CompanyResource
    {
        $company = $this->companyService->update(
            $company,
            new UpdateCompanyData(name: $request->validated('name')),
        );

        return new CompanyResource($company);
    }

    public function destroy(Company $company): Response
    {
        $this->companyService->delete($company);

        return response()->noContent();
    }
}
