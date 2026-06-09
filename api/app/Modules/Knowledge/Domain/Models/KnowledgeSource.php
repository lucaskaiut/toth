<?php

namespace App\Modules\Knowledge\Domain\Models;

use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceStatus;
use App\Modules\Knowledge\Domain\Enums\KnowledgeSourceType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class KnowledgeSource extends Model
{
    protected $fillable = [
        'company_id',
        'type',
        'title',
        'content',
        'metadata',
        'status',
        'indexed_at',
        'index_error',
    ];

    protected function casts(): array
    {
        return [
            'type' => KnowledgeSourceType::class,
            'status' => KnowledgeSourceStatus::class,
            'metadata' => 'array',
            'indexed_at' => 'datetime',
        ];
    }

    public function chunks(): HasMany
    {
        return $this->hasMany(KnowledgeChunk::class, 'source_id');
    }
}
