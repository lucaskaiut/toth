<?php

namespace App\Core\Whatsapp\DTOs;

readonly class IncomingWhatsAppMessage
{
    public function __construct(
        public string $instanceName,
        public string $phone,
        public string $content,
        public ?string $senderName = null,
        public ?string $externalId = null,
    ) {}
}
