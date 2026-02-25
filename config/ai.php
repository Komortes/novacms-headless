<?php

return [
    'provider' => env('AI_PROVIDER', 'ollama'),

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
