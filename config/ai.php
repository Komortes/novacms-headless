<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),

    'providers' => [
        'ollama' => [
            'base_url' => env('OLLAMA_BASE_URL', 'http://127.0.0.1:11434'),
            'model' => env('OLLAMA_MODEL', 'llama3.2:1b'),
            'timeout' => (int) env('OLLAMA_TIMEOUT', 90),
        ],
        'openai' => [
            'base_url' => env('OPENAI_BASE_URL', 'https://api.openai.com/v1'),
            'api_key' => env('OPENAI_API_KEY'),
            'model' => env('OPENAI_MODEL', 'gpt-4.1-mini'),
            'timeout' => (int) env('OPENAI_TIMEOUT', 90),
        ],
    ],
];
