<?php

namespace Tests\Unit\Integrations\Embedding;

use App\Integrations\Embedding\OpenAIEmbeddingProvider;
use Illuminate\Support\Facades\Http;
use RuntimeException;
use Tests\TestCase;

class OpenAIEmbeddingProviderTest extends TestCase
{
    public function test_post_includes_dimensions_when_configured(): void
    {
        Http::fake([
            'https://api.example.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 768, 0.1)],
                ],
            ]),
        ]);

        $provider = new OpenAIEmbeddingProvider(
            baseUrl: 'https://api.example.com/v1',
            apiKey: 'test-key',
            model: 'gemini-embedding-001',
            timeout: 5,
            dimensions: 768,
        );

        $embedding = $provider->embed('texto de teste');

        $this->assertCount(768, $embedding);

        Http::assertSent(function ($request) {
            $body = $request->data();

            return $request->url() === 'https://api.example.com/v1/embeddings'
                && ($body['dimensions'] ?? null) === 768
                && $body['model'] === 'gemini-embedding-001';
        });
    }

    public function test_throws_when_received_dimensions_do_not_match_expected(): void
    {
        Http::fake([
            'https://api.example.com/v1/embeddings' => Http::response([
                'data' => [
                    ['embedding' => array_fill(0, 3072, 0.1)],
                ],
            ]),
        ]);

        $provider = new OpenAIEmbeddingProvider(
            baseUrl: 'https://api.example.com/v1',
            apiKey: 'test-key',
            model: 'gemini-embedding-001',
            timeout: 5,
            dimensions: 768,
        );

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('espera 768');

        $provider->embed('texto de teste');
    }
}
