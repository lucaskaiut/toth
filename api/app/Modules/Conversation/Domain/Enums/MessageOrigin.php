<?php

namespace App\Modules\Conversation\Domain\Enums;

enum MessageOrigin: string
{
    case Customer = 'customer';
    case Ai = 'ai';
    case User = 'user';
}
