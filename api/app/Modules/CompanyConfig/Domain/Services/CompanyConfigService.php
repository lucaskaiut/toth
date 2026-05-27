<?php

namespace App\Modules\CompanyConfig\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\CompanyConfig\Domain\Enums\CompanyConfigType;
use App\Modules\CompanyConfig\Domain\Models\CompanyConfig;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CompanyConfigService
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->resolver()->get($key, $default);
    }

    /**
     * @param  array<string, array{value: mixed, type?: CompanyConfigType|string}>  $configs
     */
    public function setMany(array $configs): void
    {
        $this->setManyForCompany($this->currentCompany->id(), $configs);
    }

    /**
     * @param  array<string, array{value: mixed, type?: CompanyConfigType|string}>  $configs
     */
    public function setManyForCompany(int $companyId, array $configs): void
    {
        DB::transaction(function () use ($configs, $companyId) {
            foreach ($configs as $key => $payload) {
                $type = $payload['type'] ?? CompanyConfigType::String;
                if (is_string($type)) {
                    $type = CompanyConfigType::from($type);
                }

                $value = $this->serializeValue($payload['value'], $type);

                CompanyConfig::query()->updateOrCreate(
                    ['company_id' => $companyId, 'key' => $key],
                    ['value' => $value, 'type' => $type],
                );
            }
        });

        (new CompanyConfigResolver($companyId))->forgetCache($companyId);
    }

    /**
     * @return Collection<int, CompanyConfig>
     */
    public function all(): Collection
    {
        return CompanyConfig::query()
            ->where('company_id', $this->currentCompany->id())
            ->orderBy('key')
            ->get();
    }

    public function findCompanyByInstanceName(string $instanceName): ?int
    {
        $config = CompanyConfig::query()
            ->where('key', 'evolution.instance_name')
            ->where('value', $instanceName)
            ->first();

        return $config?->company_id;
    }

    private function resolver(): CompanyConfigResolver
    {
        return new CompanyConfigResolver($this->currentCompany->id());
    }

    private function serializeValue(mixed $value, CompanyConfigType $type): ?string
    {
        if ($value === null) {
            return null;
        }

        return match ($type) {
            CompanyConfigType::Json => json_encode($value, JSON_THROW_ON_ERROR),
            CompanyConfigType::Bool => $value ? '1' : '0',
            CompanyConfigType::Int => (string) (int) $value,
            CompanyConfigType::String => (string) $value,
        };
    }
}
