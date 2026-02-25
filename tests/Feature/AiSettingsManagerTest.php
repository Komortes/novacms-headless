<?php

namespace Tests\Feature;

use App\Models\AiSetting;
use App\Services\AiSettingsManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AiSettingsManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_applies_database_overrides_to_ai_config(): void
    {
        AiSetting::query()->create([
            'id' => 1,
            'default_provider' => 'openai',
            'ollama_base_url' => 'http://ollama.internal:11434',
            'ollama_model' => 'qwen2.5:0.5b',
            'ollama_timeout' => 45,
            'openai_base_url' => 'https://api.openai.com/v1',
            'openai_api_key' => 'test-key',
            'openai_model' => 'gpt-4o-mini',
            'openai_timeout' => 30,
        ]);

        app(AiSettingsManager::class)->applyConfigOverrides();

        $this->assertSame('openai', config('ai.provider'));
        $this->assertSame('http://ollama.internal:11434', config('ai.providers.ollama.base_url'));
        $this->assertSame('qwen2.5:0.5b', config('ai.providers.ollama.model'));
        $this->assertSame(45, config('ai.providers.ollama.timeout'));
        $this->assertSame('https://api.openai.com/v1', config('ai.providers.openai.base_url'));
        $this->assertSame('test-key', config('ai.providers.openai.api_key'));
        $this->assertSame('gpt-4o-mini', config('ai.providers.openai.model'));
        $this->assertSame(30, config('ai.providers.openai.timeout'));
    }

    public function test_it_includes_runtime_configured_model_in_model_options(): void
    {
        config()->set('ai.providers.ollama.available_models', ['qwen2.5:0.5b']);
        config()->set('ai.providers.ollama.model', 'custom-ollama-model');

        $options = app(AiSettingsManager::class)->modelOptions('ollama');

        $this->assertArrayHasKey('qwen2.5:0.5b', $options);
        $this->assertArrayHasKey('custom-ollama-model', $options);
    }
}
