<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),

    'map_reduce' => [
        'enabled' => (bool) env('AI_MAP_REDUCE_ENABLED', true),
        'min_body_chars' => (int) env('AI_MAP_REDUCE_MIN_BODY_CHARS', 5000),
        'chunk_chars' => (int) env('AI_MAP_REDUCE_CHUNK_CHARS', 3500),
        'max_chunks' => (int) env('AI_MAP_REDUCE_MAX_CHUNKS', 12),
    ],

    'summary' => [
        'auto_dispatch' => (bool) env('AI_SUMMARY_AUTO_DISPATCH', true),
    ],

    'embeddings' => [
        'provider' => env('AI_EMBEDDINGS_PROVIDER', env('AI_PROVIDER', 'ollama')),
        'model' => env('AI_EMBEDDINGS_MODEL', 'nomic-embed-text'),
        // Must match the embedding model output size: nomic-embed-text = 768,
        // mxbai-embed-large = 1024, all-minilm = 384. The pgvector column is
        // created with this size, so change both together (requires re-migration).
        'dimensions' => (int) env('AI_EMBEDDINGS_DIMENSIONS', 768),
        'chunk_chars' => (int) env('AI_EMBEDDINGS_CHUNK_CHARS', 1200),
        'max_chunks' => (int) env('AI_EMBEDDINGS_MAX_CHUNKS', 32),
        'auto_dispatch' => (bool) env('AI_EMBEDDINGS_AUTO_DISPATCH', true),
    ],

    'http_retry' => [
        'times' => (int) env('AI_HTTP_RETRY_TIMES', 2),
        'sleep_ms' => (int) env('AI_HTTP_RETRY_SLEEP_MS', 250),
    ],

    'jobs' => [
        'rate_limit_per_minute' => (int) env('AI_RATE_LIMIT_PER_MINUTE', 60),
        'summary' => [
            'queue' => env('AI_SUMMARY_QUEUE', 'ai'),
            'tries' => (int) env('AI_SUMMARY_JOB_TRIES', 3),
            'timeout' => (int) env('AI_SUMMARY_JOB_TIMEOUT', 300),
            'backoff_seconds' => (int) env('AI_SUMMARY_JOB_BACKOFF_SECONDS', 15),
        ],
        'embeddings' => [
            'queue' => env('AI_EMBEDDINGS_QUEUE', 'ai'),
            'tries' => (int) env('AI_EMBEDDINGS_JOB_TRIES', 3),
            'timeout' => (int) env('AI_EMBEDDINGS_JOB_TIMEOUT', 300),
            'backoff_seconds' => (int) env('AI_EMBEDDINGS_JOB_BACKOFF_SECONDS', 15),
        ],
    ],

    'providers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'qwen2.5:1.5b'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 90),
            'available_models' => [
                'qwen2.5:0.5b',
                'qwen2.5:1.5b',
                'llama3.2:1b',
                'llama3.2:3b',
            ],
        ],
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 90),
            'available_models' => [
                'gpt-4.1-mini',
                'gpt-4o-mini',
                'gpt-4.1',
            ],
        ],
    ],
];
