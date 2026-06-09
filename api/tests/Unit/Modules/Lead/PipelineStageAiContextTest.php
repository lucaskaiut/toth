<?php

namespace Tests\Unit\Modules\Lead;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PipelineStageAiContextTest extends TestCase
{
    use RefreshDatabase;

    public function test_build_ai_context_block_lists_all_stages_and_current(): void
    {
        $company = Company::factory()->create();
        $service = app(PipelineStageService::class);
        $service->seedForCompany($company);

        $current = $service->findBySlug($company->id, DefaultPipelineStage::Proposta->value);
        $block = $service->buildAiContextBlock($company->id, $current);

        $this->assertStringContainsString('Estágios disponíveis', $block);
        $this->assertStringContainsString('Slug: proposta', $block);
        $this->assertStringContainsString('Quando usar:', $block);
        $this->assertStringContainsString('Estágio atual do lead: Proposta (slug: proposta)', $block);
    }
}
