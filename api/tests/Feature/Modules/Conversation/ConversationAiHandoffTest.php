<?php

namespace Tests\Feature\Modules\Conversation;

use App\Core\AI\Contracts\AiClient;
use App\Core\AI\DTOs\AiChatMessage;
use App\Core\AI\DTOs\AiStructuredResponse;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Models\Message;
use App\Modules\Conversation\Domain\Services\ConversationAiProcessor;
use App\Modules\Conversation\Domain\Services\ConversationContextBuilder;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ConversationAiHandoffTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ConversationContextBuilder::class, function ($mock): void {
            $mock->shouldReceive('build')
                ->andReturn([
                    new AiChatMessage(role: 'system', content: 'Prompt de teste'),
                ]);
        });
    }

    public function test_processor_applies_handoff_when_ai_returns_requires_handoff(): void
    {
        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.api_key',
            'value' => 'test-key',
            'type' => CompanyConfigType::String,
        ]);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.model',
            'value' => 'test-model',
            'type' => CompanyConfigType::String,
        ]);

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

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'origin' => MessageOrigin::Customer,
            'content' => 'Gostaria de agendar, por favor.',
            'sent_at' => now(),
        ]);

        $this->mock(AiClient::class, function ($mock): void {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(new AiStructuredResponse(
                    message: 'Vou encaminhar para nossa equipe confirmar o agendamento.',
                    suggestedStage: 'proposta',
                    summary: 'Cliente solicitou agendamento.',
                    shouldReply: true,
                    requiresHandoff: true,
                ));
        });

        app(ConversationAiProcessor::class)->process($conversation->fresh(['lead.pipelineStage']));

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'attendance_status' => ConversationAttendanceStatus::HandoffToHuman->value,
        ]);
    }

    public function test_processor_does_not_handoff_when_ai_omits_requires_handoff(): void
    {
        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.api_key',
            'value' => 'test-key',
            'type' => CompanyConfigType::String,
        ]);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.model',
            'value' => 'test-model',
            'type' => CompanyConfigType::String,
        ]);

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

        Message::query()->create([
            'conversation_id' => $conversation->id,
            'origin' => MessageOrigin::Customer,
            'content' => 'Gostaria de agendar, por favor.',
            'sent_at' => now(),
        ]);

        $this->mock(AiClient::class, function ($mock): void {
            $mock->shouldReceive('chat')
                ->once()
                ->andReturn(new AiStructuredResponse(
                    message: 'Vou encaminhar seu atendimento para nossa equipe confirmar o agendamento.',
                    suggestedStage: 'proposta',
                    summary: 'Cliente solicitou agendamento.',
                    shouldReply: true,
                ));
        });

        app(ConversationAiProcessor::class)->process($conversation->fresh(['lead.pipelineStage']));

        $this->assertDatabaseHas('conversations', [
            'id' => $conversation->id,
            'attendance_status' => ConversationAttendanceStatus::AiEnabled->value,
        ]);
    }
}
