<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class LoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_login_with_internal_channel_returns_token_and_user(): void
    {
        $user = User::factory()->create([
            'name' => 'Admin',
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $response = $this->postJson('/api/login', [
            'channel' => 'internal',
            'data' => [
                'email' => 'admin@example.com',
                'password' => 'secret',
            ],
        ]);

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => [
                    'token',
                    'user' => ['id', 'name', 'email'],
                ],
            ])
            ->assertJsonPath('data.user.id', $user->id)
            ->assertJsonPath('data.user.email', 'admin@example.com');

        $this->assertNotEmpty($response->json('data.token'));
        $this->assertDatabaseCount('personal_access_tokens', 1);
    }

    public function test_login_returns_unauthorized_for_invalid_credentials(): void
    {
        User::factory()->create([
            'email' => 'admin@example.com',
            'password' => Hash::make('secret'),
        ]);

        $response = $this->postJson('/api/login', [
            'channel' => 'internal',
            'data' => [
                'email' => 'admin@example.com',
                'password' => 'wrong-password',
            ],
        ]);

        $response
            ->assertUnauthorized()
            ->assertJsonPath('message', 'Credenciais inválidas.');

        $this->assertDatabaseCount('personal_access_tokens', 0);
    }

    public function test_login_returns_unprocessable_for_unsupported_channel(): void
    {
        $response = $this->postJson('/api/login', [
            'channel' => 'google',
            'data' => ['token' => 'oauth-token'],
        ]);

        $response
            ->assertUnprocessable()
            ->assertJsonPath('message', 'Canal de login não suportado.');
    }

    public function test_login_validates_required_fields(): void
    {
        $response = $this->postJson('/api/login', []);

        $response
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['channel', 'data']);
    }
}
