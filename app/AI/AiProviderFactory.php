<?php

namespace App\AI;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\OllamaProvider;
use App\AI\Providers\OpenAiProvider;
use Illuminate\Http\Client\Factory as HttpFactory;

class AiProviderFactory
{
    public function __construct(
        private readonly HttpFactory $http,
    ) {
    }

    public function make(?string $provider = null): AiProviderInterface
    {
        $selectedProvider = $provider ?? (string) config('ai.provider', 'ollama');

        return match ($selectedProvider) {
            'ollama' => new OllamaProvider(
                http: $this->http,
                baseUrl: (string) config('ai.providers.ollama.base_url'),
                defaultModel: (string) config('ai.providers.ollama.model'),
                timeout: (int) config('ai.providers.ollama.timeout', 90),
            ),
            'openai' => new OpenAiProvider(
                http: $this->http,
                baseUrl: (string) config('ai.providers.openai.base_url'),
                apiKey: config('ai.providers.openai.api_key'),
                defaultModel: (string) config('ai.providers.openai.model'),
                timeout: (int) config('ai.providers.openai.timeout', 90),
            ),
            default => throw new AiProviderException("Unsupported AI provider [{$selectedProvider}]."),
        };
    }
}

