<?php

namespace Tests\Feature\Modules\Knowledge;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class KnowledgeSourceTenantTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_cannot_access_other_company_knowledge_source(): void
    {
        $companyA = Company::factory()->create();
        $companyB = Company::factory()->create();
        $userA = User::factory()->create(['company_id' => $companyA->id]);
        Sanctum::actingAs($userA);

        $foreignSource = KnowledgeSource::query()->create([
            'company_id' => $companyB->id,
            'type' => KnowledgeSourceType::Faq,
            'title' => 'FAQ externa',
            'content' => 'Conteúdo',
            'status' => 'pending',
        ]);

        $this->putJson("/api/knowledge/sources/{$foreignSource->id}", [
                'title' => 'Tentativa',
            ])
            ->assertNotFound();
    }
}
