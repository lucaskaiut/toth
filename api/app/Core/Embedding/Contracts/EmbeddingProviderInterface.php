<?php

namespace App\Core\Embedding\Contracts;

interface EmbeddingProviderInterface
{
    /**
     * @return list<float>
     */
    public function embed(string $text): array;

    public function dimensions(): int;
}
