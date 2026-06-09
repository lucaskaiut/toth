<?php

namespace App\Modules\Knowledge\Domain\Repositories;

use Illuminate\Support\Facades\DB;

class VectorEmbeddingRepository
{
    protected string $connection = 'vector';

    /**
     * @param  list<float>  $embedding
     */
    public function upsert(
        int $companyId,
        int $chunkId,
        int $sourceId,
        array $embedding,
    ): int {
        $vector = $this->formatVector($embedding);

        $existing = DB::connection($this->connection)
            ->table('knowledge_embeddings')
            ->where('chunk_id', $chunkId)
            ->value('id');

        if ($existing !== null) {
            DB::connection($this->connection)->update(
                'UPDATE knowledge_embeddings SET company_id = ?, source_id = ?, embedding = ?::vector, updated_at = NOW() WHERE chunk_id = ?',
                [$companyId, $sourceId, $vector, $chunkId]
            );

            return (int) $existing;
        }

        $id = DB::connection($this->connection)->selectOne(
            'INSERT INTO knowledge_embeddings (company_id, chunk_id, source_id, embedding, created_at, updated_at)
             VALUES (?, ?, ?, ?::vector, NOW(), NOW())
             RETURNING id',
            [$companyId, $chunkId, $sourceId, $vector]
        );

        return (int) $id->id;
    }

    public function deleteByChunkIds(int $companyId, array $chunkIds): void
    {
        if ($chunkIds === []) {
            return;
        }

        DB::connection($this->connection)
            ->table('knowledge_embeddings')
            ->where('company_id', $companyId)
            ->whereIn('chunk_id', $chunkIds)
            ->delete();
    }

    public function deleteBySourceId(int $companyId, int $sourceId): void
    {
        DB::connection($this->connection)
            ->table('knowledge_embeddings')
            ->where('company_id', $companyId)
            ->where('source_id', $sourceId)
            ->delete();
    }

    public function deleteByCompanyId(int $companyId): void
    {
        DB::connection($this->connection)
            ->table('knowledge_embeddings')
            ->where('company_id', $companyId)
            ->delete();
    }

    /**
     * @param  list<float>  $queryEmbedding
     * @return list<object{chunk_id: int, source_id: int, score: float}>
     */
    public function searchSimilar(int $companyId, array $queryEmbedding, int $limit = 8): array
    {
        $vector = $this->formatVector($queryEmbedding);

        return DB::connection($this->connection)->select(
            'SELECT chunk_id, source_id, 1 - (embedding <=> ?::vector) AS score
             FROM knowledge_embeddings
             WHERE company_id = ?
             ORDER BY embedding <=> ?::vector
             LIMIT ?',
            [$vector, $companyId, $vector, $limit]
        );
    }

    public function countByCompany(int $companyId): int
    {
        return (int) DB::connection($this->connection)
            ->table('knowledge_embeddings')
            ->where('company_id', $companyId)
            ->count();
    }

    /**
     * @param  list<float>  $embedding
     */
    private function formatVector(array $embedding): string
    {
        $parts = array_map(
            static fn (float $value): string => sprintf('%.8f', $value),
            $embedding
        );

        return '['.implode(',', $parts).']';
    }
}
