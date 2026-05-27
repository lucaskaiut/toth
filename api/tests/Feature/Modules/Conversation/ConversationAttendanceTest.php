<?php

namespace Tests\Feature\Modules\Conversation;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Jobs\DebouncedProcessConversationAiJob;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ConversationAttendanceTest extends TestCase
{
    use RefreshDatabase;

    public function test_manual_message_sets_handoff_to_human(): void
    {
        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $stage = app(PipelineStageService::class)->defaultStage($company->id);
        $lead = \App\Modules\Lead\Domain\Models\Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Cliente',
            'phone' => '5511999887766',
        ]);

        $conversation = Conversation::query()->create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'attendance_status' => ConversationAttendanceStatus::AiEnabled,
        ]);

        $response = $this->postJson("/api/conversations/{$conversation->id}/messages", [
            'content' => 'Olá, sou o atendente.',
        ]);

        $response->assertCreated();

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'attendance_status' => ConversationAttendanceStatus::HandoffToHuman->value,
        ]);
    }

    public function test_webhook_does_not_schedule_ai_when_handoff_to_human(): void
    {
        Queue::fake();

        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'evolution.instance_name',
            'value' => 'instancia-teste',
            'type' => CompanyConfigType::String,
        ]);

        $stage = app(PipelineStageService::class)->defaultStage($company->id);
        $lead = \App\Modules\Lead\Domain\Models\Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Cliente',
            'phone' => '5511999887766',
        ]);

        Conversation::query()->create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'attendance_status' => ConversationAttendanceStatus::HandoffToHuman,
        ]);

        $response = $this->postJson('/api/webhooks/whatsapp', [
            'event' => 'messages.upsert',
            'instance' => 'instancia-teste',
            'data' => [
                'key' => [
                    'remoteJid' => '5511999887766@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => ['conversation' => 'Mais uma mensagem'],
            ],
        ]);

        $response->assertOk();

        Queue::assertNotPushed(DebouncedProcessConversationAiJob::class);
    }

    public function test_can_update_attendance_status_via_api(): void
    {
        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $stage = app(PipelineStageService::class)->defaultStage($company->id);
        $lead = \App\Modules\Lead\Domain\Models\Lead::query()->create([
            'company_id' => $company->id,
            'pipeline_stage_id' => $stage->id,
            'name' => 'Cliente 2',
            'phone' => '5511888776655',
        ]);

        $conversation = Conversation::query()->create([
            'company_id' => $company->id,
            'lead_id' => $lead->id,
            'attendance_status' => ConversationAttendanceStatus::HandoffToHuman,
        ]);

        $response = $this->patchJson("/api/conversations/{$conversation->id}/attendance-status", [
            'attendance_status' => 'ai_enabled',
        ]);

        $response
            ->assertOk()
            ->assertJsonPath('data.attendance_status', 'ai_enabled');

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'attendance_status' => 'ai_enabled',
        ]);
    }
}
