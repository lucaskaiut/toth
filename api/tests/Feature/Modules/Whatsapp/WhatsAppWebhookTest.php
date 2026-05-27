<?php

namespace Tests\Feature\Modules\Whatsapp;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\Conversation\Domain\Models\Conversation;
use App\Modules\Conversation\Domain\Models\Message;
use App\Modules\Lead\Domain\Models\Lead;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class WhatsAppWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_creates_lead_conversation_and_message(): void
    {
        Queue::fake();
        config(['whatsapp.webhook_token' => 'test-webhook-token']);

        $company = Company::factory()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'evolution.instance_name',
            'value' => 'minha-instancia',
            'type' => CompanyConfigType::String,
        ]);

        $response = $this->withHeader('authorization', 'Bearer test-webhook-token')
            ->postJson('/api/webhooks/whatsapp', [
            'event' => 'messages.upsert',
            'instance' => 'minha-instancia',
            'data' => [
                'key' => [
                    'remoteJid' => '5511999999999@s.whatsapp.net',
                    'fromMe' => false,
                ],
                'message' => [
                    'conversation' => 'Olá, preciso de ajuda',
                ],
                'pushName' => 'Cliente Teste',
            ],
        ]);

        $response->assertOk()->assertJsonPath('status', 'ok');

        $this->assertDatabaseHas('leads', [
            'company_id' => $company->id,
            'phone' => '5511999999999',
        ]);

        $lead = Lead::query()->where('phone', '5511999999999')->first();
        $this->assertNotNull($lead);

        $this->assertDatabaseHas('conversations', [
            'lead_id' => $lead->id,
        ]);

        $conversation = Conversation::query()->where('lead_id', $lead->id)->first();

        $this->assertDatabaseHas('messages', [
            'conversation_id' => $conversation->id,
            'origin' => 'customer',
            'content' => 'Olá, preciso de ajuda',
        ]);
    }
}
