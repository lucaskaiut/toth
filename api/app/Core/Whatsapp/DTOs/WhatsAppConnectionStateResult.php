<?php

namespace App\Core\Whatsapp\DTOs;

readonly class WhatsAppConnectionStateResult
{
    public function __construct(
        public bool $success,
        public ?string $state = null,
        public ?string $error = null,
    ) {}

    public function isConnected(): bool
    {
        return $this->success && strtolower((string) $this->state) === 'open';
    }
}
