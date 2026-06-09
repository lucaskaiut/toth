<?php

namespace App\Core\AI\DTOs;

readonly class AiParseContext
{
    public function __construct(
        public bool $hadToolActivity = false,
        public bool $toolFailed = false,
    ) {}
}
