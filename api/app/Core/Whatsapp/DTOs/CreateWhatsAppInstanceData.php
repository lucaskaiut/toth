<?php

namespace App\Core\Whatsapp\DTOs;

readonly class CreateWhatsAppInstanceData
{
    /**
     * @param  list<string>  $webhookEvents
     * @param  array<string, string>  $webhookHeaders
     */
    public function __construct(
        public string $instanceName,
        public string $number,
        public string $webhookUrl,
        public array $webhookEvents,
        public array $webhookHeaders,
    ) {}
}
