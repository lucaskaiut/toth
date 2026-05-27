<?php

namespace App\Modules\Company\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Company\Domain\Services\CompanyWhatsAppSetupService;
use Illuminate\Http\JsonResponse;

class CompanyWhatsAppSetupController extends Controller
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
        private readonly CompanyWhatsAppSetupService $setupService,
    ) {}

    public function connect(): JsonResponse
    {
        $data = $this->setupService->connect($this->currentCompany->get());

        return response()->json(['data' => $data]);
    }

    public function connectionState(): JsonResponse
    {
        $data = $this->setupService->connectionState($this->currentCompany->get());

        return response()->json(['data' => $data]);
    }
}
