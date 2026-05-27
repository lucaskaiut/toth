<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use App\Modules\Company\Domain\Enums\CompanyStatus;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class RegisterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config([
            'whatsapp.api_key' => 'test-global-key',
            'whatsapp.base_url' => 'http://evolution.test',
        ]);

        Http::fake([
            'http://evolution.test/instance/create' => Http::response([], 201),
        ]);
    }

    public function test_register_with_internal_channel_returns_token_and_user(): void
    {
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

        $response
            ->assertCreated()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJsonPath('data.user.name', 'Admin')
            ->assertJsonPath('data.user.email', 'admin@example.com');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseHas('companies', [
            'name' => 'Acme Inc',
            'whatsapp' => '5511999887766',
            'status' => CompanyStatus::PendingWhatsappConnection->value,
        ]);
        $this->assertDatabaseHas('users', [
            'email' => 'admin@example.com',
            'company_id' => Company::query()->value('id'),
        ]);
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_register_returns_unprocessable_when_email_already_exists(): void
    {
        User::factory()->create(['email' => 'admin@example.com']);

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

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Não foi possível concluir o cadastro.');

        $this->assertDatabaseCount('companies', 0);
        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_register_returns_unprocessable_for_unsupported_channel(): void
    {
        $response = $this->postJson('/api/register', [
            'channel' => 'google',
            'data' => ['token' => 'oauth-token'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Canal de cadastro não suportado.');
    }

    public function test_register_validates_required_fields(): void
    {
        $response = $this->postJson('/api/register', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['channel', 'data']);
    }
}
