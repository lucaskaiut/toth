<?php

namespace App\Core\Whatsapp\DTOs;

readonly class WhatsAppConnectResult
{
    public function __construct(
        public bool $success,
        public ?string $pairingCode = null,
        public ?string $code = null,
        public ?string $base64 = null,
        public ?string $error = null,
    ) {}
}
