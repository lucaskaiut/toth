<?php

namespace Tests\Feature\Modules\Company;

use App\Models\User;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class InitializeCompanyMiddlewareTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_with_company_can_access_protected_routes(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')->assertOk();
    }

    public function test_authenticated_user_without_company_is_forbidden(): void
    {
        $user = User::factory()->create(['company_id' => null]);

        Sanctum::actingAs($user);

        $this->getJson('/api/me')
            ->assertForbidden()
            ->assertJsonPath('message', 'Usuário sem empresa vinculada.');
    }

    public function test_current_company_is_available_during_request(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);

        Route::middleware(['auth:sanctum', 'company'])->get('/api/test-current-company', function (CurrentCompany $currentCompany) {
            return response()->json(['company_id' => $currentCompany->id()]);
        });

        Sanctum::actingAs($user);

        $this->getJson('/api/test-current-company')
            ->assertOk()
            ->assertJsonPath('company_id', $company->id);
    }
}
