<?php

return [
    'driver' => env('HORUS_LOG_DRIVER', 'horus'),
    'base_url' => env('HORUS_LOG_BASE_URL', 'https://horus-api.lucaskaiut.com.br'),
    'token' => env('HORUS_LOG_TOKEN', ''),
    'source' => env('HORUS_LOG_SOURCE', env('APP_NAME', 'toth')),
    'environment' => env('HORUS_LOG_ENVIRONMENT', env('APP_ENV', 'local')),
    'enabled' => env('HORUS_LOG_ENABLED', true),
    'timeout' => (int) env('HORUS_LOG_TIMEOUT', 5),
];

