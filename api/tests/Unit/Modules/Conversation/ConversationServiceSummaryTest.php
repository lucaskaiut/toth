<?php

namespace Tests\Unit\Modules\Conversation;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Services\ConversationService;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationServiceSummaryTest extends TestCase
{
    use RefreshDatabase;

    public function test_summary_is_truncated_to_configured_max_length(): void
    {
        config(['ai.summary_max_length' => 50]);

        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);
        $stage = app(PipelineStageService::class)->defaultStage($company->id);

        $lead = Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Cliente',
            'phone' => '5511999887766',
        ]);

        $conversation = Conversation::query()->create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
        ]);

        $longSummary = str_repeat('a', 120);

        app(ConversationService::class)->updateSummary($conversation, $longSummary);

        $conversation->refresh();

        $this->assertSame(50, mb_strlen((string) $conversation->summary));
    }
}
