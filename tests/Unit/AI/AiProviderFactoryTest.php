<?php

namespace Tests\Unit\AI;

use App\AI\AiProviderFactory;
use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\OllamaProvider;
use App\AI\Providers\OpenAiProvider;
use Tests\TestCase;

class AiProviderFactoryTest extends TestCase
{
    public function test_it_resolves_ollama_provider_from_config(): void
    {
        config()->set('ai.provider', 'ollama');
        config()->set('ai.providers.ollama.base_url', 'http://ollama.local');
        config()->set('ai.providers.ollama.model', 'llama3.1');
        config()->set('ai.providers.ollama.timeout', 15);

        $provider = app(AiProviderFactory::class)->make();

        $this->assertInstanceOf(OllamaProvider::class, $provider);
    }

    public function test_it_resolves_openai_provider_from_config(): void
    {
        config()->set('ai.provider', 'openai');
        config()->set('ai.providers.openai.base_url', 'https://api.openai.com/v1');
        config()->set('ai.providers.openai.api_key', 'test-key');
        config()->set('ai.providers.openai.model', 'gpt-4.1-mini');
        config()->set('ai.providers.openai.timeout', 20);

        $provider = app(AiProviderFactory::class)->make();

        $this->assertInstanceOf(OpenAiProvider::class, $provider);
    }

    public function test_it_throws_for_unsupported_provider(): void
    {
        config()->set('ai.provider', 'invalid-provider');

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('Unsupported AI provider');

        app(AiProviderFactory::class)->make();
    }
}

