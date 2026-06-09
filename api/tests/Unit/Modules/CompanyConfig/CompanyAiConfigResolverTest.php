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
            'embedding.openai.model' => 'fallback-embedding',
            'embedding.dimensions' => 768,
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
        $this->assertSame('fallback-embedding', $config->embeddingModel);
        $this->assertSame(768, $config->embeddingDimensions);
        $this->assertTrue($config->isConfigured());
        $this->assertTrue($config->hasEmbeddingCredentials());
    }

    public function test_resolves_embedding_dimensions_from_company_config(): void
    {
        config(['embedding.dimensions' => 768]);

        $company = Company::factory()->create();

        CompanyConfig::query()->create([
            'company_id' => $company->id,
            'key' => 'embedding.dimensions',
            'value' => '1536',
            'type' => CompanyConfigType::Int,
        ]);

        $config = app(CompanyAiConfigResolver::class)->resolve($company->id);

        $this->assertSame(1536, $config->embeddingDimensions);
    }

    public function test_uses_global_defaults_when_company_values_are_missing(): void
    {
        config([
            'ai.default_base_url' => 'https://fallback.example.com/v1',
            'ai.default_model' => 'fallback-model',
            'embedding.openai.model' => 'fallback-embedding',
            'embedding.dimensions' => 768,
        ]);

        $company = Company::factory()->create();

        $config = app(CompanyAiConfigResolver::class)->resolve($company->id);

        $this->assertSame('https://fallback.example.com/v1', $config->baseUrl);
        $this->assertSame('', $config->apiKey);
        $this->assertSame('fallback-model', $config->model);
        $this->assertSame('fallback-embedding', $config->embeddingModel);
        $this->assertSame(768, $config->embeddingDimensions);
        $this->assertFalse($config->isConfigured());
        $this->assertFalse($config->hasEmbeddingCredentials());
    }
}
