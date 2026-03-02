<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),

    'map_reduce' => [
        'enabled' => (bool) env('AI_MAP_REDUCE_ENABLED', true),
        'min_body_chars' => (int) env('AI_MAP_REDUCE_MIN_BODY_CHARS', 5000),
        'chunk_chars' => (int) env('AI_MAP_REDUCE_CHUNK_CHARS', 3500),
        'max_chunks' => (int) env('AI_MAP_REDUCE_MAX_CHUNKS', 12),
    ],

    'embeddings' => [
        'provider' => env('AI_EMBEDDINGS_PROVIDER', env('AI_PROVIDER', 'ollama')),
        'model' => env('AI_EMBEDDINGS_MODEL', 'nomic-embed-text'),
        'dimensions' => (int) env('AI_EMBEDDINGS_DIMENSIONS', 1024),
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
