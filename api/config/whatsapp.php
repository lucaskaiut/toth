<?php

return [
    'driver' => env('WHATSAPP_DRIVER', 'evolution'),
    'base_url' => env('WHATSAPP_BASE_URL', 'http://localhost:8080'),
    'api_key' => env('WHATSAPP_API_KEY', ''),
    'timeout' => (int) env('WHATSAPP_TIMEOUT', 30),
    'webhook_token' => env('WHATSAPP_WEBHOOK_TOKEN', ''),
    'webhook_events' => [
        'MESSAGES_UPSERT',
        'MESSAGES_UPDATE',
        'CONNECTION_UPDATE',
        'SEND_MESSAGE',
    ],
];
