<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    protected $connection = 'vector';

    public function up(): void
    {
        $dimensions = (int) config('embedding.dimensions', 768);

        DB::connection('vector')->statement('CREATE EXTENSION IF NOT EXISTS vector');

        DB::connection('vector')->statement(<<<SQL
            CREATE TABLE IF NOT EXISTS knowledge_embeddings (
                id BIGSERIAL PRIMARY KEY,
                company_id BIGINT NOT NULL,
                chunk_id BIGINT NOT NULL UNIQUE,
                source_id BIGINT NOT NULL,
                embedding vector({$dimensions}) NOT NULL,
                created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP,
                updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT CURRENT_TIMESTAMP
            )
        SQL);

        DB::connection('vector')->statement(
            'CREATE INDEX IF NOT EXISTS knowledge_embeddings_company_id_idx ON knowledge_embeddings (company_id)'
        );
    }

    public function down(): void
    {
        DB::connection('vector')->statement('DROP TABLE IF EXISTS knowledge_embeddings');
    }
};
