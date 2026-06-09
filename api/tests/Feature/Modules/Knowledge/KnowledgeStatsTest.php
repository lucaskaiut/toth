<?php

namespace Tests\Feature\Modules\Knowledge;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KnowledgeStatsTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_fetch_knowledge_stats(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        KnowledgeSource::query()->create([
            'company_id' => $company->id,
            'type' => KnowledgeSourceType::Faq,
            'title' => 'FAQ teste',
            'content' => 'Resposta',
            'status' => KnowledgeSourceStatus::Indexed,
            'indexed_at' => now(),
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/knowledge/stats');

        $response
            ->assertOk()
            ->assertJsonPath('data.sources_total', 1)
            ->assertJsonStructure([
                'data' => [
                    'sources_total',
                    'chunks_total',
                    'vectors_total',
                    'last_indexed_at',
                    'by_status',
                ],
            ]);
    }
}
