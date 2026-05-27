<?php

namespace App\Modules\Lead\Domain\Models;

use App\Modules\Company\Domain\Models\Company;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\PipelineStageFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PipelineStage extends Model
{
    /** @use HasFactory<PipelineStageFactory> */
    use HasFactory;

    protected static function newFactory(): PipelineStageFactory
    {
        return PipelineStageFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'slug',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'position' => 'integer',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function leads(): HasMany
    {
        return $this->hasMany(Lead::class);
    }
}
