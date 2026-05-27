<?php

namespace App\Modules\Lead\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Lead\Domain\DTOs\UpdateLeadData;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Models\PipelineStage;
use App\Modules\Lead\Domain\Services\LeadService;
use App\Modules\Lead\Http\Requests\MoveLeadStageRequest;
use App\Modules\Lead\Http\Requests\UpdateLeadRequest;
use App\Modules\Lead\Http\Resources\LeadResource;
use App\Modules\Realtime\Events\LeadStageChanged;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class LeadController extends Controller
{
    public function __construct(
        private readonly LeadService $leadService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return LeadResource::collection($this->leadService->all());
    }

    public function show(Lead $lead): LeadResource
    {
        return new LeadResource($this->leadService->find($lead->id));
    }

    public function update(UpdateLeadRequest $request, Lead $lead): LeadResource
    {
        $lead = $this->leadService->update(
            $this->leadService->find($lead->id),
            new UpdateLeadData(
                name: $request->validated('name'),
                email: $request->validated('email'),
                companyName: $request->validated('company_name'),
                notes: $request->validated('notes'),
            ),
        );

        return new LeadResource($lead);
    }

    public function moveStage(MoveLeadStageRequest $request, Lead $lead): LeadResource
    {
        $lead = $this->leadService->find($lead->id);
        $stage = PipelineStage::query()
            ->where('company_id', $lead->company_id)
            ->findOrFail($request->validated('pipeline_stage_id'));

        $updated = $this->leadService->moveToStage($lead, $stage);

        broadcast(new LeadStageChanged($updated->company_id, $updated))->toOthers();

        return new LeadResource($updated);
    }
}
