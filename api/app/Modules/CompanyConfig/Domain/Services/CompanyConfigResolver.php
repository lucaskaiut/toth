<?php

namespace App\Modules\CompanyConfig\Domain\Services;

use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Cache;

class CompanyConfigResolver
{
    public function __construct(
        private readonly ?int $companyId = null,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->companyId === null) {
            return $default;
        }

        $configs = $this->allForCompany($this->companyId);

        if (! array_key_exists($key, $configs)) {
            return $default;
        }

        return $this->castValue($configs[$key]['value'], $configs[$key]['type']);
    }

    public function has(string $key): bool
    {
        if ($this->companyId === null) {
            return false;
        }

        return array_key_exists($key, $this->allForCompany($this->companyId));
    }

    public function forgetCache(int $companyId): void
    {
        $this->cache()->forget($this->cacheKey($companyId));
    }

    /**
     * @return array<string, array{value: ?string, type: CompanyConfigType}>
     */
    private function allForCompany(int $companyId): array
    {
        return $this->cache()->remember(
            $this->cacheKey($companyId),
            config('company.config_cache_ttl'),
            function () use ($companyId) {
                return CompanyConfig::query()
                    ->where('company_id', $companyId)
                    ->get()
                    ->mapWithKeys(fn (CompanyConfig $config) => [
                        $config->key => [
                            'value' => $config->value,
                            'type' => $config->type,
                        ],
                    ])
                    ->all();
            },
        );
    }

    private function castValue(?string $value, CompanyConfigType $type): mixed
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            CompanyConfigType::Int => (int) $value,
            CompanyConfigType::Bool => filter_var($value, FILTER_VALIDATE_BOOLEAN),
            CompanyConfigType::Json => json_decode($value, true),
            CompanyConfigType::String => $value,
        };
    }

    private function cacheKey(int $companyId): string
    {
        return config('company.config_cache_prefix').':'.$companyId;
    }

    private function cache(): CacheRepository
    {
        return Cache::store(config('cache.default'));
    }
}
