<?php

namespace Tests\Feature\Modules\Lead;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class LeadTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_list_leads(): void
    {
        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/leads');

        $response->assertOk()->assertJsonStructure(['data']);
    }

    public function test_authenticated_user_can_list_pipeline_stages(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/pipeline/stages');

        $response
            ->assertOk()
            ->assertJsonCount(4, 'data');
    }
}
