<?php

return [
    'chunk_size_tokens' => (int) env('KNOWLEDGE_CHUNK_SIZE_TOKENS', 800),
    'chunk_overlap_tokens' => (int) env('KNOWLEDGE_CHUNK_OVERLAP_TOKENS', 150),
    'retrieval_top_k' => (int) env('KNOWLEDGE_RETRIEVAL_TOP_K', 8),
    'context_top_k' => (int) env('KNOWLEDGE_CONTEXT_TOP_K', 8),
    'max_upload_kb' => (int) env('KNOWLEDGE_MAX_UPLOAD_KB', 10240),
    'allowed_document_mimes' => [
        'text/plain',
        'text/markdown',
        'application/pdf',
        'application/msword',
        'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
    ],
    'queue' => env('KNOWLEDGE_QUEUE', 'default'),
];
