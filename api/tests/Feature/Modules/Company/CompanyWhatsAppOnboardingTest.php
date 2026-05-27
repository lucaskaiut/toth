<?php

namespace Tests\Feature\Modules\Company;

use App\Models\User;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Company\Domain\Services\CompanyInstanceNameGenerator;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\Lead\Domain\Services\PipelineStageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class CompanyWhatsAppOnboardingTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.api_key' => 'test-global-key',
            'whatsapp.base_url' => 'http://evolution.test',
            'whatsapp.webhook_token' => 'webhook-secret',
            'app.url' => 'http://localhost',
        ]);
    }

    public function test_register_provisions_instance_and_sets_pending_status(): void
    {
        Http::fake([
            'http://evolution.test/instance/create' => Http::response([
                'instance' => ['instanceName' => 'toth_1_abc'],
            ], 201),
        ]);

        $response = $this->postJson('/api/register', [
            'channel' => 'internal',
            'data' => [
                'company_name' => 'Acme Inc',
                'whatsapp' => '5511999887766',
                'name' => 'Admin',
                'email' => 'admin@example.com',
                'password' => 'secret123',
            ],
        ]);

        $response->assertCreated();

        $company = Company::query()->first();
        $this->assertNotNull($company);
        $this->assertSame('5511999887766', $company->whatsapp);
        $this->assertSame(CompanyStatus::PendingWhatsappConnection, $company->status);

        $instanceName = app(CompanyInstanceNameGenerator::class)->generate($company->id);
        $this->assertDatabaseHas('company_configs', [
            'company_id' => $company->id,
            'key' => 'evolution.instance_name',
            'value' => $instanceName,
        ]);

        Http::assertSent(function ($request) use ($instanceName) {
            $body = $request->data();

            return $request->url() === 'http://evolution.test/instance/create'
                && $body['instanceName'] === $instanceName
                && $body['number'] === '5511999887766'
                && $body['qrcode'] === true
                && $body['webhook']['url'] === 'http://localhost/api/webhooks/whatsapp'
                && $body['webhook']['byEvents'] === true
                && $body['webhook']['base64'] === true;
        });
    }

    public function test_pending_company_cannot_access_kanban_routes(): void
    {
        $company = Company::factory()->pendingWhatsapp()->create();
        app(PipelineStageService::class)->seedForCompany($company);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        $this->getJson('/api/leads')
            ->assertForbidden()
            ->assertJsonPath('code', 'company_pending_whatsapp');
    }

    public function test_connect_endpoint_returns_qr_data(): void
    {
        $company = Company::factory()->pendingWhatsapp()->create();
        $instanceName = app(CompanyInstanceNameGenerator::class)->generate($company->id);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'evolution.instance_name',
            'value' => $instanceName,
            'type' => 'string',
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        Http::fake([
            "http://evolution.test/instance/connect/{$instanceName}" => Http::response([
                'base64' => 'abc123',
                'pairingCode' => 'ABCD-1234',
            ], 200),
        ]);

        $response = $this->getJson('/api/company/whatsapp/connect');

        $response
            ->assertOk()
            ->assertJsonPath('data.instance_name', $instanceName)
            ->assertJsonPath('data.base64', 'abc123')
            ->assertJsonPath('data.pairing_code', 'ABCD-1234');
    }

    public function test_connection_state_activates_company_when_open(): void
    {
        $company = Company::factory()->pendingWhatsapp()->create();
        $instanceName = app(CompanyInstanceNameGenerator::class)->generate($company->id);

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'evolution.instance_name',
            'value' => $instanceName,
            'type' => 'string',
        ]);

        $user = User::factory()->create(['company_id' => $company->id]);
        Sanctum::actingAs($user);

        Http::fake([
            "http://evolution.test/instance/connectionState/{$instanceName}" => Http::response([
                'instance' => ['state' => 'open'],
            ], 200),
        ]);

        $response = $this->getJson('/api/company/whatsapp/connection-state');

        $response
            ->assertOk()
            ->assertJsonPath('data.connected', true)
            ->assertJsonPath('data.company_status', 'active');

        $this->assertSame(CompanyStatus::Active, $company->fresh()->status);
    }
}
