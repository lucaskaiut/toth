<?php

namespace App\Modules\Conversation\Domain\Models;

use App\Models\User;
use App\Modules\Conversation\Domain\Enums\MessageOrigin;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Message extends Model
{
    use HasFactory;

    protected $fillable = [
        'conversation_id',
        'origin',
        'user_id',
        'content',
        'sent_at',
    ];

    protected function casts(): array
    {
        return [
            'origin' => MessageOrigin::class,
            'sent_at' => 'datetime',
        ];
    }

    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
