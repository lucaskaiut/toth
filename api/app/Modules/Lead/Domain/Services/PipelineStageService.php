<?php

namespace App\Modules\Lead\Domain\Services;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Enums\DefaultPipelineStage;
use App\Modules\Lead\Domain\Models\PipelineStage;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

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
                    'description' => $definition['description'],
                    'ai_instruction' => $definition['ai_instruction'],
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

    public function findForCompany(int $companyId, int $stageId): PipelineStage
    {
        return PipelineStage::query()
            ->where('company_id', $companyId)
            ->findOrFail($stageId);
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

    /**
     * @param  array{name: string, description: string, ai_instruction?: string|null}  $data
     */
    public function create(int $companyId, array $data): PipelineStage
    {
        return DB::transaction(function () use ($companyId, $data) {
            $slug = $this->generateUniqueSlug($companyId, $data['name']);

            return PipelineStage::query()->create([
                'company_id' => $companyId,
                'name' => $data['name'],
                'slug' => $slug,
                'description' => $data['description'],
                'ai_instruction' => $data['ai_instruction'] ?? null,
                'position' => $this->nextPosition($companyId),
            ]);
        });
    }

    /**
     * @param  array{name?: string, description?: string, ai_instruction?: string|null}  $data
     */
    public function update(PipelineStage $stage, array $data): PipelineStage
    {
        if (isset($data['name'])) {
            $stage->name = $data['name'];
        }

        if (isset($data['description'])) {
            $stage->description = $data['description'];
        }

        if (array_key_exists('ai_instruction', $data)) {
            $stage->ai_instruction = $data['ai_instruction'];
        }

        $stage->save();

        return $stage->fresh();
    }

    public function delete(PipelineStage $stage): void
    {
        if ($stage->leads()->exists()) {
            throw ValidationException::withMessages([
                'stage' => ['Não é possível excluir um estágio com leads vinculados.'],
            ]);
        }

        DB::transaction(function () use ($stage) {
            $companyId = $stage->company_id;
            $stage->delete();
            $this->renumberPositions($companyId);
        });
    }

    /**
     * @param  list<int>  $orderedStageIds
     */
    public function reorder(int $companyId, array $orderedStageIds): Collection
    {
        $stages = PipelineStage::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $orderedStageIds)
            ->get()
            ->keyBy('id');

        if ($stages->count() !== count($orderedStageIds)) {
            throw ValidationException::withMessages([
                'stages' => ['Lista de estágios inválida para esta empresa.'],
            ]);
        }

        $totalStages = PipelineStage::query()->where('company_id', $companyId)->count();

        if ($totalStages !== count($orderedStageIds)) {
            throw ValidationException::withMessages([
                'stages' => ['Informe todos os estágios na ordem desejada.'],
            ]);
        }

        DB::transaction(function () use ($companyId, $orderedStageIds) {
            $this->applyOrderedPositions($companyId, $orderedStageIds);
        });

        return $this->forCompany($companyId);
    }

    /**
     * @param  list<int>  $orderedStageIds
     */
    private function applyOrderedPositions(int $companyId, array $orderedStageIds): void
    {
        foreach ($orderedStageIds as $index => $stageId) {
            PipelineStage::query()
                ->where('company_id', $companyId)
                ->where('id', $stageId)
                ->update(['position' => 1000 + $index]);
        }

        foreach ($orderedStageIds as $index => $stageId) {
            PipelineStage::query()
                ->where('company_id', $companyId)
                ->where('id', $stageId)
                ->update(['position' => $index]);
        }
    }

    private function renumberPositions(int $companyId): void
    {
        $orderedStageIds = PipelineStage::query()
            ->where('company_id', $companyId)
            ->orderBy('position')
            ->pluck('id')
            ->all();

        if ($orderedStageIds === []) {
            return;
        }

        $this->applyOrderedPositions($companyId, $orderedStageIds);
    }

    /**
     * Monta bloco de contexto semântico para o LLM.
     */
    public function buildAiContextBlock(int $companyId, ?PipelineStage $currentStage = null): string
    {
        $stages = $this->forCompany($companyId);

        if ($stages->isEmpty()) {
            return 'Estágios disponíveis: nenhum estágio cadastrado.';
        }

        $lines = ['Estágios disponíveis (use suggested_stage apenas com um destes slugs):'];

        foreach ($stages as $stage) {
            $lines[] = "- Slug: {$stage->slug}";
            $lines[] = "  Nome: {$stage->name}";
            $lines[] = "  Descrição: {$stage->description}";

            if ($stage->ai_instruction) {
                $lines[] = "  Quando usar: {$stage->ai_instruction}";
            }
        }

        if ($currentStage !== null) {
            $lines[] = '';
            $lines[] = "Estágio atual do lead: {$currentStage->name} (slug: {$currentStage->slug}).";
        }

        return implode("\n", $lines);
    }

    private function nextPosition(int $companyId): int
    {
        $max = PipelineStage::query()
            ->where('company_id', $companyId)
            ->max('position');

        return $max === null ? 0 : ((int) $max + 1);
    }

    private function generateUniqueSlug(int $companyId, string $name): string
    {
        $base = $this->slugify($name);

        if ($base === '') {
            $base = 'stage';
        }

        $slug = $base;
        $suffix = 2;

        while ($this->findBySlug($companyId, $slug) !== null) {
            $slug = "{$base}_{$suffix}";
            $suffix++;
        }

        return $slug;
    }

    private function slugify(string $name): string
    {
        $ascii = Str::ascii(trim($name));

        return Str::slug($ascii, '_');
    }
}
