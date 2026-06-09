<?php

namespace Tests\Unit\Modules\CompanyConfig;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use App\Modules\CompanyConfig\Domain\Services\CompanyAiConfigResolver;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CompanyAiConfigResolverTest extends TestCase
{
    use RefreshDatabase;

    public function test_resolves_ai_settings_from_company_configs(): void
    {
        config([
            'ai.default_base_url' => 'https://fallback.example.com/v1',
            'ai.default_model' => 'fallback-model',
        ]);

        $company = Company::factory()->create();

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.base_url',
            'value' => 'https://company.example.com/v1',
            'type' => CompanyConfigType::String,
        ]);
        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.api_key',
            'value' => 'company-key',
            'type' => CompanyConfigType::String,
        ]);
        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'ai.model',
            'value' => 'company-model',
            'type' => CompanyConfigType::String,
        ]);

        $config = app(CompanyAiConfigResolver::class)->resolve($company->id);

        $this->assertSame('https://company.example.com/v1', $config->baseUrl);
        $this->assertSame('company-key', $config->apiKey);
        $this->assertSame('company-model', $config->model);
        $this->assertTrue($config->isConfigured());
    }

    public function test_uses_global_defaults_when_company_values_are_missing(): void
    {
        config([
            'ai.default_base_url' => 'https://fallback.example.com/v1',
            'ai.default_model' => 'fallback-model',
        ]);

        $company = Company::factory()->create();

        $config = app(CompanyAiConfigResolver::class)->resolve($company->id);

        $this->assertSame('https://fallback.example.com/v1', $config->baseUrl);
        $this->assertSame('', $config->apiKey);
        $this->assertSame('fallback-model', $config->model);
        $this->assertFalse($config->isConfigured());
    }
}
