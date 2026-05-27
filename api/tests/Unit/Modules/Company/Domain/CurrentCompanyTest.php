<?php

namespace Tests\Unit\Modules\Company\Domain;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Company\Domain\Exceptions\CompanyNotInitializedException;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CurrentCompanyTest extends TestCase
{
    use RefreshDatabase;

    public function test_is_set_returns_false_before_initialization(): void
    {
        $currentCompany = new CurrentCompany;

        $this->assertFalse($currentCompany->isSet());
    }

    public function test_set_and_get_returns_company(): void
    {
        $company = Company::factory()->create();
        $currentCompany = new CurrentCompany;

        $currentCompany->set($company);

        $this->assertTrue($currentCompany->isSet());
        $this->assertTrue($company->is($currentCompany->get()));
    }

    public function test_id_returns_company_primary_key(): void
    {
        $company = Company::factory()->create();
        $currentCompany = new CurrentCompany;
        $currentCompany->set($company);

        $this->assertSame($company->id, $currentCompany->id());
    }

    public function test_get_throws_when_not_initialized(): void
    {
        $currentCompany = new CurrentCompany;

        $this->expectException(CompanyNotInitializedException::class);

        $currentCompany->get();
    }
}
