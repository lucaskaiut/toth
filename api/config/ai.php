<?php

return [
    'driver' => env('AI_DRIVER', 'openai_compatible'),
    'base_url' => env('AI_BASE_URL', 'https://api.openai.com/v1'),
    'timeout' => (int) env('AI_TIMEOUT', 60),
    'recent_messages_limit' => (int) env('AI_RECENT_MESSAGES_LIMIT', 20),
    'debounce_min_seconds' => (int) env('AI_DEBOUNCE_MIN_SECONDS', 8),
    'debounce_max_seconds' => (int) env('AI_DEBOUNCE_MAX_SECONDS', 15),
    'default_model' => env('AI_DEFAULT_MODEL', 'gpt-4o-mini'),
    'default_system_prompt' => env('AI_DEFAULT_SYSTEM_PROMPT', 'Você é um assistente comercial prestativo. Responda sempre em JSON com as chaves: message, suggested_stage, summary. suggested_stage deve ser um destes valores: novo_lead, qualificacao, proposta, fechado.'),
];
