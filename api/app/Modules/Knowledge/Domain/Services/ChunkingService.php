<?php

namespace App\Modules\Knowledge\Domain\Services;

class ChunkingService
{
    private int $chunkSizeChars;

    private int $overlapChars;

    public function __construct()
    {
        $chunkTokens = (int) config('knowledge.chunk_size_tokens', 800);
        $overlapTokens = (int) config('knowledge.chunk_overlap_tokens', 150);

        $this->chunkSizeChars = $chunkTokens * 4;
        $this->overlapChars = $overlapTokens * 4;
    }

    /**
     * @return list<string>
     */
    public function chunk(string $rawContent): array
    {
        $normalized = $this->normalize($rawContent);

        if ($normalized === '') {
            return [];
        }

        if (mb_strlen($normalized) <= $this->chunkSizeChars) {
            return [$normalized];
        }

        $chunks = [];
        $start = 0;
        $length = mb_strlen($normalized);

        while ($start < $length) {
            $end = min($start + $this->chunkSizeChars, $length);
            $piece = mb_substr($normalized, $start, $end - $start);

            if ($piece !== '') {
                $chunks[] = $piece;
            }

            if ($end >= $length) {
                break;
            }

            $start = max(0, $end - $this->overlapChars);
        }

        return $chunks;
    }

    public function normalize(string $content): string
    {
        $content = html_entity_decode(strip_tags($content), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $content = preg_replace('/\r\n|\r/', "\n", $content) ?? $content;
        $content = preg_replace("/[ \t]+/", ' ', $content) ?? $content;
        $content = preg_replace("/\n{3,}/", "\n\n", $content) ?? $content;

        return trim($content);
    }
}
