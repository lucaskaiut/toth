<?php

namespace App\Modules\Conversation\Domain\Models;

use App\Modules\Conversation\Domain\Enums\ConversationAttendanceStatus;
use App\Modules\Company\Domain\Models\Company;
use App\Modules\Lead\Domain\Models\Lead;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    protected static function newFactory(): ConversationFactory
    {
        return ConversationFactory::new();
    }

    protected $fillable = [
        'company_id',
        'lead_id',
        'summary',
        'attendance_status',
    ];

    protected function casts(): array
    {
        return [
            'attendance_status' => ConversationAttendanceStatus::class,
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function lead(): BelongsTo
    {
        return $this->belongsTo(Lead::class);
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class)->orderBy('sent_at');
    }
}
