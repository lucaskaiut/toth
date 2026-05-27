<?php

return [
    'config_cache_ttl' => (int) env('COMPANY_CONFIG_CACHE_TTL', 3600),
    'config_cache_prefix' => env('COMPANY_CONFIG_CACHE_PREFIX', 'company_config'),
];
