<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Modules\Company\Domain\CurrentCompany;
use App\Modules\Knowledge\Domain\DTOs\KnowledgeSearchResult;
use App\Modules\Knowledge\Domain\Models\KnowledgeChunk;
use App\Modules\Knowledge\Domain\Repositories\VectorEmbeddingRepository;

class RetrievalService
{
    public function __construct(
        private readonly CurrentCompany $currentCompany,
        private readonly EmbeddingService $embeddingService,
        private readonly VectorEmbeddingRepository $vectorRepository,
    ) {}

    /**
     * @return list<KnowledgeSearchResult>
     */
    public function search(string $query, ?int $limit = null, ?int $companyId = null): array
    {
        $companyId ??= $this->currentCompany->id();
        $limit ??= (int) config('knowledge.retrieval_top_k', 8);

        $query = trim($query);
        if ($query === '') {
            return [];
        }

        $queryEmbedding = $this->embeddingService->embed($query);
        $matches = $this->vectorRepository->searchSimilar($companyId, $queryEmbedding, $limit);

        if ($matches === []) {
            return [];
        }

        $chunkIds = array_map(static fn ($row): int => (int) $row->chunk_id, $matches);

        $chunks = KnowledgeChunk::query()
            ->where('company_id', $companyId)
            ->whereIn('id', $chunkIds)
            ->with('source')
            ->get()
            ->keyBy('id');

        $results = [];

        foreach ($matches as $match) {
            $chunk = $chunks->get((int) $match->chunk_id);
            if ($chunk === null || $chunk->source === null) {
                continue;
            }

            $source = $chunk->source;

            $results[] = new KnowledgeSearchResult(
                chunkId: $chunk->id,
                sourceId: $source->id,
                sourceType: $source->type->value,
                sourceTitle: $source->title,
                chunkIndex: $chunk->chunk_index,
                content: $chunk->content,
                score: (float) $match->score,
                sourceMetadata: $source->metadata,
                chunkMetadata: $chunk->metadata,
            );
        }

        return $results;
    }

    public function buildContext(string $query, ?int $limit = null, ?int $companyId = null): string
    {
        $results = $this->search($query, $limit, $companyId);

        if ($results === []) {
            return '';
        }

        $sections = ["[CHUNKS RELEVANTES]\n"];

        foreach ($results as $index => $result) {
            $sections[] = sprintf(
                "--- Resultado %d (score: %.4f) | %s: %s ---\n%s\n",
                $index + 1,
                $result->score,
                $result->sourceType,
                $result->sourceTitle,
                $result->content
            );
        }

        return implode("\n", $sections);
    }
}
