<?php

namespace App\Modules\Lead\Domain\Services;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;

class PipelineStageService
{
    public function seedForCompany(Company $company): void
    {
        foreach (DefaultPipelineStage::definitions() as $definition) {
            PipelineStage::query()->firstOrCreate(
                [
                    'company_id' => $company->id,
                    'slug' => $definition['slug'],
                ],
                [
                    'name' => $definition['name'],
                    'position' => $definition['position'],
                ],
            );
        }
    }

    /**
     * @return Collection<int, PipelineStage>
     */
    public function forCompany(int $companyId): Collection
    {
        return PipelineStage::query()
            ->where('company_id', $companyId)
            ->orderBy('position')
            ->get();
    }

    public function findBySlug(int $companyId, string $slug): ?PipelineStage
    {
        return PipelineStage::query()
            ->where('company_id', $companyId)
            ->where('slug', $slug)
            ->first();
    }

    public function defaultStage(int $companyId): PipelineStage
    {
        return PipelineStage::query()
            ->where('company_id', $companyId)
            ->where('slug', DefaultPipelineStage::NovoLead->value)
            ->firstOrFail();
    }
}
