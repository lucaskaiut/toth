<?php

namespace App\Core\Whatsapp\DTOs;

readonly class SendMessageResult
{
    public function __construct(
        public bool $success,
        public ?string $externalId = null,
        public ?string $error = null,
    ) {}
}
