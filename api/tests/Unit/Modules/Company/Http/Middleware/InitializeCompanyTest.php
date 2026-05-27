<?php

namespace Tests\Unit\Modules\Company\Http\Middleware;

use App\Models\User;
use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Company\Http\Middleware\InitializeCompany;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use Tests\TestCase;

class InitializeCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_sets_current_company_from_authenticated_user(): void
    {
        $company = Company::factory()->create();
        $user = User::factory()->create(['company_id' => $company->id]);
        $currentCompany = new CurrentCompany;
        $middleware = new InitializeCompany($currentCompany);

        $request = Request::create('/api/me', 'GET');
        $request->setUserResolver(fn () => $user);

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertTrue($company->is($currentCompany->get()));
    }

    public function test_returns_forbidden_when_user_has_no_company(): void
    {
        $user = User::factory()->create(['company_id' => null]);
        $currentCompany = new CurrentCompany;
        $middleware = new InitializeCompany($currentCompany);

        $request = Request::create('/api/me', 'GET');
        $request->setUserResolver(fn () => $user);

        $this->expectExceptionMessage('Usuário sem empresa vinculada.');

        $middleware->handle($request, fn () => response()->noContent());
    }

    public function test_passes_through_when_request_has_no_user(): void
    {
        $currentCompany = new CurrentCompany;
        $middleware = new InitializeCompany($currentCompany);

        $request = Request::create('/api/me', 'GET');

        $response = $middleware->handle($request, fn () => response()->noContent());

        $this->assertSame(Response::HTTP_NO_CONTENT, $response->getStatusCode());
        $this->assertFalse($currentCompany->isSet());
    }
}
