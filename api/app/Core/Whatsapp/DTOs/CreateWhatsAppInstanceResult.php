<?php

namespace App\Core\Whatsapp\DTOs;

readonly class CreateWhatsAppInstanceResult
{
    public function __construct(
        public bool $success,
        public ?string $error = null,
        public ?array $raw = null,
    ) {}
}
