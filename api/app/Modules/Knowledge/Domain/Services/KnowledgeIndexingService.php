<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Models\KnowledgeChunk;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Repositories\VectorEmbeddingRepository;
use Illuminate\Support\Facades\DB;
use Throwable;

class KnowledgeIndexingService
{
    public function __construct(
        private readonly ChunkingService $chunkingService,
        private readonly EmbeddingService $embeddingService,
        private readonly VectorEmbeddingRepository $vectorRepository,
    ) {}

    public function indexSource(KnowledgeSource $source): void
    {
        $source->update([
            'status' => KnowledgeSourceStatus::Indexing,
            'index_error' => null,
        ]);

        try {
            $content = $this->resolveContent($source);
            $chunks = $this->chunkingService->chunk($content);
            $this->persistChunks($source, $chunks, withEmbeddings: true);

            $source->update([
                'status' => KnowledgeSourceStatus::Indexed,
                'indexed_at' => now(),
                'index_error' => null,
            ]);
        } catch (Throwable $exception) {
            $source->update([
                'status' => KnowledgeSourceStatus::Error,
                'index_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    /**
     * Indexa chunks textuais sem embeddings (útil para seed/demo quando Ollama está offline).
     */
    public function indexSourceWithoutEmbeddings(KnowledgeSource $source): void
    {
        $source->update([
            'status' => KnowledgeSourceStatus::Indexing,
            'index_error' => null,
        ]);

        try {
            $content = $this->resolveContent($source);
            $chunks = $this->chunkingService->chunk($content);
            $this->persistChunks($source, $chunks, withEmbeddings: false);

            $source->update([
                'status' => KnowledgeSourceStatus::Indexed,
                'indexed_at' => now(),
                'index_error' => null,
            ]);
        } catch (Throwable $exception) {
            $source->update([
                'status' => KnowledgeSourceStatus::Error,
                'index_error' => $exception->getMessage(),
            ]);

            throw $exception;
        }
    }

    public function reindexCompany(int $companyId): void
    {
        KnowledgeSource::query()
            ->where('company_id', $companyId)
            ->orderBy('id')
            ->each(function (KnowledgeSource $source): void {
                $this->indexSource($source);
            });
    }

    /**
     * @param  list<string>  $chunks
     */
    private function persistChunks(KnowledgeSource $source, array $chunks, bool $withEmbeddings): void
    {
        DB::transaction(function () use ($source, $chunks, $withEmbeddings) {
            $oldChunkIds = KnowledgeChunk::query()
                ->where('company_id', $source->company_id)
                ->where('source_id', $source->id)
                ->pluck('id')
                ->all();

            $this->vectorRepository->deleteByChunkIds($source->company_id, $oldChunkIds);

            KnowledgeChunk::query()
                ->where('source_id', $source->id)
                ->delete();

            foreach ($chunks as $index => $chunkContent) {
                $chunk = KnowledgeChunk::query()->create([
                    'company_id' => $source->company_id,
                    'source_id' => $source->id,
                    'chunk_index' => $index,
                    'content' => $chunkContent,
                    'metadata' => [
                        'source_type' => $source->type->value,
                        'source_title' => $source->title,
                    ],
                ]);

                if (! $withEmbeddings) {
                    continue;
                }

                $embedding = $this->embeddingService->embed($chunkContent);
                $vectorId = $this->vectorRepository->upsert(
                    $source->company_id,
                    $chunk->id,
                    $source->id,
                    $embedding,
                );

                $chunk->update(['embedding_reference' => (string) $vectorId]);
            }
        });
    }

    private function resolveContent(KnowledgeSource $source): string
    {
        $content = trim((string) $source->content);

        if ($content !== '') {
            return $content;
        }

        $filePath = $source->metadata['storage_path'] ?? null;
        if (is_string($filePath) && is_readable($filePath)) {
            $extracted = file_get_contents($filePath);
            if (is_string($extracted) && trim($extracted) !== '') {
                return $extracted;
            }
        }

        return $source->title;
    }
}
