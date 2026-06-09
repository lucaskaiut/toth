<?php

namespace App\Modules\Knowledge\Domain\Services;

use App\Core\Embedding\Contracts\EmbeddingProviderInterface;

class EmbeddingService
{
    public function __construct(
        private readonly EmbeddingProviderInterface $provider,
    ) {}

    /**
     * @return list<float>
     */
    public function embed(string $text): array
    {
        return $this->provider->embed($text);
    }

    public function dimensions(): int
    {
        return $this->provider->dimensions();
    }
}
