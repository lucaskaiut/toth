<?php

namespace Tests\Feature\Modules\CompanyConfig;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\CompanyConfig\Domain\Services\CompanyConfigResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyConfigCacheTest extends TestCase
{
    use RefreshDatabase;

    public function test_cache_is_invalidated_when_config_is_updated_directly(): void
    {
        $company = Company::factory()->create();

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'integration.provider',
            'value' => '',
            'type' => CompanyConfigType::String,
        ]);

        $resolver = new CompanyConfigResolver($company->id);
        $this->assertSame('', $resolver->get('integration.provider'));

        $config = CompanyConfig::query()
            ->where('company_id', $company->id)
            ->where('key', 'integration.provider')
            ->firstOrFail();
        $config->value = 'nox';
        $config->save();

        $this->assertSame('nox', $resolver->get('integration.provider'));
    }
}
