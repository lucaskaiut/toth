<?php

return [
    'driver' => env('EMBEDDING_DRIVER', 'ollama'),

    'dimensions' => (int) env('EMBEDDING_DIMENSIONS', 768),

    'ollama' => [
        'url' => env('OLLAMA_URL', 'http://host.docker.internal:11434'),
        'model' => env('OLLAMA_EMBEDDING_MODEL', 'nomic-embed-text'),
        'timeout' => (int) env('EMBEDDING_TIMEOUT', 120),
    ],

    'openai' => [
        'base_url' => env('OPENAI_EMBEDDING_BASE_URL', env('AI_BASE_URL', 'https://api.openai.com/v1')),
        'api_key' => env('OPENAI_EMBEDDING_API_KEY', env('OPENAI_API_KEY')),
        'model' => env('OPENAI_EMBEDDING_MODEL', 'text-embedding-3-small'),
        'timeout' => (int) env('EMBEDDING_TIMEOUT', 120),
    ],
];
