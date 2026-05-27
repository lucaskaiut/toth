<?php

namespace App\Modules\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use App\Modules\Lead\Http\Resources\PipelineStageResource;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class PipelineStageController extends Controller
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
        private readonly PipelineStageService $pipelineStageService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        $this->pipelineStageService->seedForCompany($this->currentCompany->get());

        return PipelineStageResource::collection(
            $this->pipelineStageService->forCompany($this->currentCompany->id()),
        );
    }
}
