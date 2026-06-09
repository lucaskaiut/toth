<?php

namespace App\Modules\Lead\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Lead\Domain\DTOs\UpdateLeadData;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class LeadService
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
        private readonly PipelineStageService $pipelineStageService,
    ) {}

    /**
     * @return Collection<int, Lead>
     */
    public function all(): Collection
    {
        return Lead::query()
            ->where('company_id', $this->currentCompany->id())
            ->with(['pipelineStage', 'conversation'])
            ->orderByDesc('updated_at')
            ->get();
    }

    public function find(int $id): Lead
    {
        return Lead::query()
            ->where('company_id', $this->currentCompany->id())
            ->with(['pipelineStage', 'conversation'])
            ->findOrFail($id);
    }

    public function findOrCreateByPhone(string $phone, ?string $name = null): Lead
    {
        $companyId = $this->currentCompany->id();
        $normalizedPhone = $this->normalizePhone($phone);

        $lead = Lead::query()
            ->where('company_id', $companyId)
            ->where('phone', $normalizedPhone)
            ->first();

        if ($lead !== null) {
            return $lead;
        }

        $stage = $this->pipelineStageService->defaultStage($companyId);

        return Lead::query()->create([
            'company_id' => $companyId,
            'pipeline_stage_id' => $stage->id,
            'name' => $name ?: $normalizedPhone,
            'phone' => $normalizedPhone,
        ]);
    }

    public function findOrCreateByPhoneForCompany(int $companyId, string $phone, ?string $name = null): Lead
    {
        $normalizedPhone = $this->normalizePhone($phone);

        $lead = Lead::query()
            ->where('company_id', $companyId)
            ->where('phone', $normalizedPhone)
            ->first();

        if ($lead !== null) {
            return $lead;
        }

        $stage = $this->pipelineStageService->defaultStage($companyId);

        return Lead::query()->create([
            'company_id' => $companyId,
            'pipeline_stage_id' => $stage->id,
            'name' => $name ?: $normalizedPhone,
            'phone' => $normalizedPhone,
        ]);
    }

    public function update(Lead $lead, UpdateLeadData $data): Lead
    {
        return DB::transaction(function () use ($lead, $data) {
            $lead->fill(array_filter([
                'name' => $data->name,
                'email' => $data->email,
                'company_name' => $data->companyName,
                'notes' => $data->notes,
            ], fn ($value) => $value !== null));

            $lead->save();

            return $lead->fresh(['pipelineStage', 'conversation']);
        });
    }

    public function moveToStage(Lead $lead, PipelineStage $stage): Lead
    {
        $lead->pipeline_stage_id = $stage->id;
        $lead->save();

        return $lead->fresh(['pipelineStage', 'conversation']);
    }

    public function moveToStageBySlug(Lead $lead, string $slug): Lead
    {
        $stage = $this->pipelineStageService->findBySlug($lead->company_id, $slug);

        if ($stage === null) {
            return $lead;
        }

        return $this->moveToStage($lead, $stage);
    }

    /**
     * Movimentação automática pela IA: ignora slug ausente/inválido e bloqueia regressão no funil.
     */
    public function moveToStageBySlugForAi(Lead $lead, ?string $slug): Lead
    {
        if ($slug === null || $slug === '') {
            return $lead;
        }

        $stage = $this->pipelineStageService->findBySlug($lead->company_id, $slug);

        if ($stage === null) {
            return $lead;
        }

        $lead->loadMissing('pipelineStage');
        $current = $lead->pipelineStage;

        if ($current !== null && $stage->position < $current->position) {
            return $lead;
        }

        return $this->moveToStage($lead, $stage);
    }

    private function normalizePhone(string $phone): string
    {
        return preg_replace('/\D+/', '', $phone) ?? $phone;
    }
}
