<?php

namespace Tests\Feature\Modules\Lead;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Models\PipelineStage;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class PipelineStageTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_pipeline_stages_with_semantic_fields(): void
    {
        [$user] = $this->actingAsCompanyUser();

        $response = $this->getJson('/api/pipeline/stages');

        $response
            ->assertOk()
            ->assertJsonCount(4, 'data')
            ->assertJsonStructure([
                'data' => [
                    ['id', 'name', 'slug', 'position', 'description', 'ai_instruction'],
                ],
            ]);
    }

    public function test_can_create_custom_pipeline_stage(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();

        $response = $this->postJson('/api/pipeline/stages', [
            'name' => 'Negociação Avançada',
            'description' => 'Cliente em negociação final de condições.',
            'ai_instruction' => 'Use quando houver contraproposta comercial.',
        ]);

        $response
            ->assertCreated()
            ->assertJsonPath('data.name', 'Negociação Avançada')
            ->assertJsonPath('data.slug', 'negociacao_avancada')
            ->assertJsonPath('data.position', 4);

        $this->assertDatabaseHas('pipeline_stages', [
            'company_id' => $company->id,
            'slug' => 'negociacao_avancada',
        ]);
    }

    public function test_can_update_pipeline_stage_without_changing_slug(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();
        $stage = PipelineStage::query()
            ->where('company_id', $company->id)
            ->where('slug', 'proposta')
            ->firstOrFail();

        $response = $this->putJson("/api/pipeline/stages/{$stage->id}", [
            'name' => 'Proposta Comercial',
            'description' => 'Descrição atualizada.',
            'ai_instruction' => 'Instrução atualizada.',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.slug', 'proposta')
            ->assertJsonPath('data.name', 'Proposta Comercial');

        $this->assertDatabaseHas('pipeline_stages', [
            'id' => $stage->id,
            'slug' => 'proposta',
            'name' => 'Proposta Comercial',
        ]);
    }

    public function test_cannot_delete_stage_with_leads(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();
        $stage = PipelineStage::query()
            ->where('company_id', $company->id)
            ->where('slug', 'novo_lead')
            ->firstOrFail();

        Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Cliente',
            'phone' => '5511999887766',
        ]);

        $response = $this->deleteJson("/api/pipeline/stages/{$stage->id}");

        $response->assertStatus(422);
    }

    public function test_can_delete_stage_without_leads(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();

        $create = $this->postJson('/api/pipeline/stages', [
            'name' => 'Temporário',
            'description' => 'Estágio temporário para exclusão.',
        ]);

        $stageId = (int) $create->json('data.id');

        $response = $this->deleteJson("/api/pipeline/stages/{$stageId}");

        $response->assertOk();
        $this->assertDatabaseMissing('pipeline_stages', ['id' => $stageId]);
    }

    public function test_can_reorder_pipeline_stages(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();

        $stages = app(PipelineStageService::class)->forCompany($company->id);
        $reordered = $stages->reverse()->values()->pluck('id')->all();

        $response = $this->patchJson('/api/pipeline/stages/reorder', [
            'stages' => $reordered,
        ]);

        $response->assertOk()->assertJsonCount($stages->count(), 'data');

        $this->assertSame(
            $reordered,
            collect($response->json('data'))->pluck('id')->all(),
        );
    }

    public function test_updated_stage_name_persists_after_listing_stages(): void
    {
        [$user, $company] = $this->actingAsCompanyUser();
        $stage = PipelineStage::query()
            ->where('company_id', $company->id)
            ->where('slug', 'proposta')
            ->firstOrFail();

        $this->putJson("/api/pipeline/stages/{$stage->id}", [
            'name' => 'Proposta Comercial Renomeada',
            'description' => $stage->description,
        ])->assertOk();

        $response = $this->getJson('/api/pipeline/stages');

        $response->assertOk();

        $updated = collect($response->json('data'))->firstWhere('slug', 'proposta');

        $this->assertSame('Proposta Comercial Renomeada', $updated['name'] ?? null);
    }

    public function test_cannot_access_stage_from_another_company(): void
    {
        [$user] = $this->actingAsCompanyUser();
        $otherCompany = Company::factory()->create(['status' => 'active']);
        app(PipelineStageService::class)->seedForCompany($otherCompany);

        $foreignStage = PipelineStage::query()
            ->where('company_id', $otherCompany->id)
            ->firstOrFail();

        $this->putJson("/api/pipeline/stages/{$foreignStage->id}", [
            'name' => 'Hack',
            'description' => 'Hack',
        ])->assertNotFound();
    }

    /**
     * @return array{0: User, 1: Company}
     */
    private function actingAsCompanyUser(): array
    {
        $company = Company::factory()->create(['status' => 'active']);
        app(PipelineStageService::class)->seedForCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        return [$user, $company];
    }
}
