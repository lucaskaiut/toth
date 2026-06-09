<?php

namespace App\Modules\Knowledge\Domain\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KnowledgeChunk extends Model
{
    protected $fillable = [
        'company_id',
        'source_id',
        'chunk_index',
        'content',
        'metadata',
        'embedding_reference',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }

    public function source(): BelongsTo
    {
        return $this->belongsTo(KnowledgeSource::class, 'source_id');
    }
}
