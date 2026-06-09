<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Knowledge\Domain\Actions\DispatchSourceIndexingAction;
use App\Modules\Knowledge\Domain\DTOs\CreateKnowledgeSourceData;
use App\Modules\Knowledge\Domain\DTOs\UpdateKnowledgeSourceData;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeChunk;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Repositories\VectorEmbeddingRepository;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class KnowledgeSourceService
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
        private readonly DispatchSourceIndexingAction $dispatchIndexing,
        private readonly VectorEmbeddingRepository $vectorRepository,
    ) {}

    /**
     * @return Collection<int, KnowledgeSource>
     */
    public function all(?KnowledgeSourceType $type = null): Collection
    {
        $query = KnowledgeSource::query()
            ->where('company_id', $this->currentCompany->id())
            ->orderByDesc('updated_at');

        if ($type !== null) {
            $query->where('type', $type->value);
        }

        return $query->get();
    }

    public function find(int $id): KnowledgeSource
    {
        return KnowledgeSource::query()
            ->where('company_id', $this->currentCompany->id())
            ->findOrFail($id);
    }

    public function create(CreateKnowledgeSourceData $data, bool $dispatchIndex = true): KnowledgeSource
    {
        $source = KnowledgeSource::query()->create([
            'company_id' => $this->currentCompany->id(),
            'type' => $data->type,
            'title' => $data->title,
            'content' => $data->content,
            'metadata' => $data->metadata,
            'status' => KnowledgeSourceStatus::Pending,
        ]);

        if ($dispatchIndex) {
            $this->dispatchIndexing->handle($source);
        }

        return $source->fresh();
    }

    public function update(KnowledgeSource $source, UpdateKnowledgeSourceData $data, bool $dispatchIndex = true): KnowledgeSource
    {
        $this->assertBelongsToCurrentCompany($source);

        $source->update(array_filter([
            'title' => $data->title,
            'content' => $data->content,
            'metadata' => $data->metadata,
            'status' => KnowledgeSourceStatus::Pending,
            'index_error' => null,
        ], static fn ($value) => $value !== null));

        if ($dispatchIndex) {
            $this->dispatchIndexing->handle($source->fresh());
        }

        return $source->fresh();
    }

    public function delete(KnowledgeSource $source): void
    {
        $this->assertBelongsToCurrentCompany($source);

        $chunkIds = $source->chunks()->pluck('id')->all();
        $this->vectorRepository->deleteByChunkIds($source->company_id, $chunkIds);
        $this->vectorRepository->deleteBySourceId($source->company_id, $source->id);

        $source->chunks()->delete();
        $source->delete();
    }

    public function storeDocument(UploadedFile $file, ?string $title = null): KnowledgeSource
    {
        $companyId = $this->currentCompany->id();
        $disk = Storage::disk('local');
        $path = $disk->putFile("knowledge/{$companyId}/documents", $file);
        $absolutePath = $disk->path($path);

        $content = $this->extractDocumentText($absolutePath, $file->getClientMimeType());

        return $this->create(new CreateKnowledgeSourceData(
            type: KnowledgeSourceType::Document,
            title: $title ?? $file->getClientOriginalName(),
            content: $content,
            metadata: [
                'original_name' => $file->getClientOriginalName(),
                'mime_type' => $file->getClientMimeType(),
                'storage_path' => $absolutePath,
                'storage_disk_path' => $path,
                'size' => $file->getSize(),
            ],
        ));
    }

    /**
     * @return array{
     *     sources_total: int,
     *     chunks_total: int,
     *     vectors_total: int,
     *     last_indexed_at: ?string,
     *     by_status: array<string, int>
     * }
     */
    public function stats(): array
    {
        $companyId = $this->currentCompany->id();

        $sources = KnowledgeSource::query()->where('company_id', $companyId);

        $byStatus = $sources->clone()
            ->selectRaw('status, COUNT(*) as total')
            ->groupBy('status')
            ->pluck('total', 'status')
            ->all();

        $lastIndexedAt = $sources->clone()->max('indexed_at');

        return [
            'sources_total' => (int) $sources->count(),
            'chunks_total' => (int) KnowledgeChunk::query()->where('company_id', $companyId)->count(),
            'vectors_total' => $this->vectorRepository->countByCompany($companyId),
            'last_indexed_at' => $lastIndexedAt !== null
                ? Carbon::parse($lastIndexedAt)->toIso8601String()
                : null,
            'by_status' => $byStatus,
        ];
    }

    public function upsertSingleton(
        KnowledgeSourceType $type,
        string $title,
        ?string $content,
        ?array $metadata = null,
    ): KnowledgeSource {
        $existing = KnowledgeSource::query()
            ->where('company_id', $this->currentCompany->id())
            ->where('type', $type->value)
            ->first();

        if ($existing === null) {
            return $this->create(new CreateKnowledgeSourceData(
                type: $type,
                title: $title,
                content: $content,
                metadata: $metadata,
            ));
        }

        return $this->update($existing, new UpdateKnowledgeSourceData(
            title: $title,
            content: $content,
            metadata: $metadata ?? $existing->metadata,
        ));
    }

    private function extractDocumentText(string $path, ?string $mime): string
    {
        if (in_array($mime, ['text/plain', 'text/markdown'], true)) {
            return trim((string) file_get_contents($path));
        }

        return '';
    }

    private function assertBelongsToCurrentCompany(KnowledgeSource $source): void
    {
        if ($source->company_id !== $this->currentCompany->id()) {
            abort(403, 'Fonte de conhecimento não pertence à empresa atual.');
        }
    }
}
