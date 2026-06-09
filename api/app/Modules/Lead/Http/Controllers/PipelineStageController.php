<?php

namespace App\Modules\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Lead\Domain\Models\PipelineStage;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use App\Modules\Lead\Http\Requests\ReorderPipelineStagesRequest;
use App\Modules\Lead\Http\Requests\StorePipelineStageRequest;
use App\Modules\Lead\Http\Requests\UpdatePipelineStageRequest;
use App\Modules\Lead\Http\Resources\PipelineStageResource;
use Illuminate\Http\JsonResponse;
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

    public function store(StorePipelineStageRequest $request): PipelineStageResource
    {
        $stage = $this->pipelineStageService->create(
            $this->currentCompany->id(),
            $request->validated(),
        );

        return new PipelineStageResource($stage);
    }

    public function update(UpdatePipelineStageRequest $request, PipelineStage $stage): PipelineStageResource
    {
        $updated = $this->pipelineStageService->update($stage, $request->validated());

        return new PipelineStageResource($updated);
    }

    public function destroy(PipelineStage $stage): JsonResponse
    {
        $this->pipelineStageService->delete($stage);

        return response()->json(['message' => 'Estágio excluído com sucesso.']);
    }

    public function reorder(ReorderPipelineStagesRequest $request): AnonymousResourceCollection
    {
        $stages = $this->pipelineStageService->reorder(
            $this->currentCompany->id(),
            $request->validated('stages'),
        );

        return PipelineStageResource::collection($stages);
    }
}
