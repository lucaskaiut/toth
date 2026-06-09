<?php

namespace Tests\Unit\Modules\Lead;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Services\LeadService;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LeadServiceStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_ai_move_keeps_stage_when_slug_is_null(): void
    {
        [$lead, $service] = $this->leadInStage(DefaultPipelineStage::Proposta);

        $result = $service->moveToStageBySlugForAi($lead, null);

        $this->assertSame($lead->pipeline_stage_id, $result->pipeline_stage_id);
    }

    public function test_ai_move_keeps_stage_when_slug_is_invalid(): void
    {
        [$lead, $service] = $this->leadInStage(DefaultPipelineStage::Proposta);

        $result = $service->moveToStageBySlugForAi($lead, 'estagio_inexistente');

        $this->assertSame($lead->pipeline_stage_id, $result->pipeline_stage_id);
    }

    public function test_ai_move_blocks_regression(): void
    {
        [$lead, $service] = $this->leadInStage(DefaultPipelineStage::Proposta);

        $result = $service->moveToStageBySlugForAi($lead, DefaultPipelineStage::NovoLead->value);

        $this->assertSame($lead->pipeline_stage_id, $result->pipeline_stage_id);
    }

    public function test_ai_move_allows_advance(): void
    {
        [$lead, $service] = $this->leadInStage(DefaultPipelineStage::Qualificacao);
        $stageService = app(PipelineStageService::class);
        $fechado = $stageService->findBySlug($lead->company_id, DefaultPipelineStage::Fechado->value);

        $result = $service->moveToStageBySlugForAi($lead, DefaultPipelineStage::Fechado->value);

        $this->assertSame($fechado->id, $result->pipeline_stage_id);
    }

    public function test_manual_move_allows_regression(): void
    {
        [$lead, $service] = $this->leadInStage(DefaultPipelineStage::Proposta);
        $stageService = app(PipelineStageService::class);
        $novoLead = $stageService->findBySlug($lead->company_id, DefaultPipelineStage::NovoLead->value);

        $result = $service->moveToStageBySlug($lead, DefaultPipelineStage::NovoLead->value);

        $this->assertSame($novoLead->id, $result->pipeline_stage_id);
    }

    /**
     * @return array{0: Lead, 1: LeadService}
     */
    private function leadInStage(DefaultPipelineStage $stage): array
    {
        $company = Company::factory()->create();
        $stageService = app(PipelineStageService::class);
        $stageService->seedForCompany($company);

        $pipelineStage = $stageService->findBySlug($company->id, $stage->value);

        $lead = Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $pipelineStage->id,
            'name' => 'Cliente',
            'phone' => '5511999887766',
        ]);

        return [$lead->fresh(['pipelineStage']), app(LeadService::class)];
    }
}
