<?php

namespace App\Core\Whatsapp\Contracts;

use App\Core\Whatsapp\DTOs\CreateWhatsAppInstanceData;
use App\Core\Whatsapp\DTOs\CreateWhatsAppInstanceResult;
use App\Core\Whatsapp\DTOs\IncomingWhatsAppMessage;
use App\Core\Whatsapp\DTOs\OutgoingWhatsAppMessage;
use App\Core\Whatsapp\DTOs\SendMessageResult;
use App\Core\Whatsapp\DTOs\WhatsAppConnectResult;
use App\Core\Whatsapp\DTOs\WhatsAppConnectionStateResult;

interface WhatsAppClient
{
    public function send(OutgoingWhatsAppMessage $message): SendMessageResult;

    public function parseWebhook(array $payload): ?IncomingWhatsAppMessage;

    public function createInstance(CreateWhatsAppInstanceData $data): CreateWhatsAppInstanceResult;

    public function connectInstance(string $instanceName): WhatsAppConnectResult;

    public function getConnectionState(string $instanceName): WhatsAppConnectionStateResult;
}
