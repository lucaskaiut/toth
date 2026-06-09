<?php

namespace Tests\Unit\Modules\Knowledge;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use App\Modules\Knowledge\Domain\Models\KnowledgeSource;
use App\Modules\Knowledge\Domain\Repositories\VectorEmbeddingRepository;
use App\Modules\Knowledge\Domain\Services\EmbeddingService;
use App\Modules\Knowledge\Domain\Services\KnowledgeIndexingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use RuntimeException;
use Tests\TestCase;

class KnowledgeIndexingServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_indexes_source_with_matching_embedding_dimensions(): void
    {
        $company = Company::factory()->create();

        $source = KnowledgeSource::query()->create([
            'company_id' => $company->id,
            'type' => KnowledgeSourceType::Faq,
            'title' => 'Vacinação',
            'content' => 'Informações sobre vacinação de cães e gatos.',
            'status' => KnowledgeSourceStatus::Pending,
        ]);

        $this->mock(EmbeddingService::class, function ($mock) use ($company): void {
            $mock->shouldReceive('embedForCompany')
                ->once()
                ->with($company->id, 'Informações sobre vacinação de cães e gatos.')
                ->andReturn(array_fill(0, 768, 0.1));
        });

        $this->mock(VectorEmbeddingRepository::class, function ($mock): void {
            $mock->shouldReceive('deleteByChunkIds')->once()->andReturnNull();
            $mock->shouldReceive('upsert')
                ->once()
                ->withArgs(function (int $companyId, int $chunkId, int $sourceId, array $embedding): bool {
                    return $companyId > 0
                        && $chunkId > 0
                        && $sourceId > 0
                        && count($embedding) === 768;
                })
                ->andReturn(42);
        });

        app(KnowledgeIndexingService::class)->indexSource($source->fresh());

        $source->refresh();

        $this->assertSame(KnowledgeSourceStatus::Indexed, $source->status);
        $this->assertNull($source->index_error);
        $this->assertNotNull($source->indexed_at);
    }

    public function test_marks_source_as_error_with_explicit_dimension_message(): void
    {
        $company = Company::factory()->create();

        $source = KnowledgeSource::query()->create([
            'company_id' => $company->id,
            'type' => KnowledgeSourceType::Faq,
            'title' => 'Vacinação',
            'content' => 'Informações sobre vacinação.',
            'status' => KnowledgeSourceStatus::Pending,
        ]);

        $this->mock(EmbeddingService::class, function ($mock): void {
            $mock->shouldReceive('embedForCompany')
                ->once()
                ->andThrow(new RuntimeException(
                    'Embedding retornou 3072 dimensões, mas o sistema espera 768. Verifique embedding.dimensions e o modelo configurado.'
                ));
        });

        $this->mock(VectorEmbeddingRepository::class, function ($mock): void {
            $mock->shouldReceive('deleteByChunkIds')->once()->andReturnNull();
            $mock->shouldNotReceive('upsert');
        });

        try {
            app(KnowledgeIndexingService::class)->indexSource($source->fresh());
        } catch (RuntimeException) {
            // esperado — job re-lança após marcar source
        }

        $source->refresh();

        $this->assertSame(KnowledgeSourceStatus::Error, $source->status);
        $this->assertStringContainsString('espera 768', (string) $source->index_error);
    }
}
