<?php

namespace App\Core\Whatsapp\DTOs;

readonly class OutgoingWhatsAppMessage
{
    public function __construct(
        public string $phone,
        public string $content,
        public string $instanceName,
    ) {}
}
