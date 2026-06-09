<?php

namespace App\Modules\Knowledge\Domain\Enums;

enum KnowledgeSourceStatus: string
{
    case Pending = 'pending';
    case Indexing = 'indexing';
    case Indexed = 'indexed';
    case Error = 'error';
}
