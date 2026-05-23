<?php

namespace App\Modules\Company\Domain\Services;

use App\Modules\Company\Domain\DTOs\CreateCompanyData;
use App\Modules\Company\Domain\DTOs\UpdateCompanyData;
use App\Modules\Company\Domain\Models\Company;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class CompanyService
{
    public function all(): Collection
    {
        return Company::query()
            ->orderBy('name')
            ->get();
    }

    public function find(int $id): Company
    {
        return Company::query()->findOrFail($id);
    }

    public function create(CreateCompanyData $data): Company
    {
        $name = $this->normalizeName($data->name);

        return DB::transaction(function () use ($name) {
            return Company::query()->create([
                'name' => $name,
            ]);
        });
    }

    public function update(Company $company, UpdateCompanyData $data): Company
    {
        if ($data->name === null) {
            throw new InvalidArgumentException('Nenhum dado informado para atualização da company.');
        }

        $name = $this->normalizeName($data->name);

        return DB::transaction(function () use ($company, $name) {
            $company->fill(['name' => $name]);
            $company->save();

            return $company->fresh();
        });
    }

    public function delete(Company $company): void
    {
        DB::transaction(function () use ($company) {
            $company->delete();
        });
    }

    private function normalizeName(string $name): string
    {
        $normalized = trim($name);

        if ($normalized === '') {
            throw new InvalidArgumentException('O nome da company é obrigatório.');
        }

        return $normalized;
    }
}
