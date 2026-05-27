<?php

namespace Tests\Feature\Modules\Auth;

use App\Models\User;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_returns_authenticated_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create([
            'company_id' => $company->id,
            'name' => 'Admin',
            'email' => 'admin@example.com',
        ]);

        Sanctum::actingAs($user);

        $response = $this->getJson('/api/me');

        $response
            ->assertOk()
            ->assertJsonStructure([
                'data' => ['id', 'name', 'email'],
            ])
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Admin')
            ->assertJsonPath('data.email', 'admin@example.com');
    }

    public function test_returns_unauthorized_without_authentication(): void
    {
        $response = $this->getJson('/api/me');

        $response->assertUnauthorized();
    }
}
