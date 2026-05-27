<?php

namespace App\Modules\Lead\Domain\Models;

use App\Modules\Company\Domain\Models\Company;
use App\Modules\Conversation\Domain\Models\Conversation;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\LeadFactory;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Lead extends Model
{
    /** @use HasFactory<LeadFactory> */
    use HasFactory;

    protected static function newFactory(): LeadFactory
    {
        return LeadFactory::new();
    }

    protected $fillable = [
        'company_id',
        'pipeline_stage_id',
        'name',
        'phone',
        'email',
        'company_name',
        'notes',
    ];

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function pipelineStage(): BelongsTo
    {
        return $this->belongsTo(PipelineStage::class);
    }

    public function conversation(): HasOne
    {
        return $this->hasOne(Conversation::class);
    }
}
