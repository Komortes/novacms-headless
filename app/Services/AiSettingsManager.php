<?php

namespace App\Services;

use App\Models\AiSetting;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Schema;
use Throwable;

class AiSettingsManager
{
    /**
     * @return array<string, array{label: string, models: array<string, string>}>
     */
    private function generationProfiles(): array
    {
        return [
            'fast' => [
                'label' => 'Fast (draft checks)',
                'models' => [
                    'ollama' => 'qwen2.5:0.5b',
                    'openai' => 'gpt-4o-mini',
                ],
            ],
            'balanced' => [
                'label' => 'Balanced (default)',
                'models' => [
                    'ollama' => 'qwen2.5:1.5b',
                    'openai' => 'gpt-4.1-mini',
                ],
            ],
            'quality' => [
                'label' => 'Quality (deeper output)',
                'models' => [
                    'ollama' => 'llama3.2:3b',
                    'openai' => 'gpt-4.1',
                ],
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public function providerOptions(): array
    {
        return [
            'ollama' => 'Ollama (local)',
            'openai' => 'OpenAI-compatible',
        ];
    }

    /**
     * @return array<string, string>
     */
    public function modelOptions(string $provider): array
    {
        if (! array_key_exists($provider, $this->providerOptions())) {
            return [];
        }

        $options = [];

        foreach ((array) config("ai.providers.{$provider}.available_models", []) as $model) {
            if (! is_string($model) || trim($model) === '') {
                continue;
            }

            $options[$model] = $model;
        }

        $configuredModel = trim((string) config("ai.providers.{$provider}.model", ''));
        if ($configuredModel !== '' && ! array_key_exists($configuredModel, $options)) {
            $options = [$configuredModel => $configuredModel] + $options;
        }

        return $options;
    }

    /**
     * @return array<string, string>
     */
    public function profileOptions(): array
    {
        $options = [];

        foreach ($this->generationProfiles() as $key => $profile) {
            $options[$key] = $profile['label'];
        }

        return $options;
    }

    /**
     * @return array<string, array{label: string, models: array<string, string>}>
     */
    public function profiles(): array
    {
        return $this->generationProfiles();
    }

    public function modelForProfile(string $provider, ?string $profile): ?string
    {
        $resolvedProfile = $this->generationProfiles()[$profile ?? ''] ?? null;

        if (! $resolvedProfile) {
            return null;
        }

        $model = $resolvedProfile['models'][$provider] ?? null;

        return is_string($model) && trim($model) !== '' ? $model : null;
    }

    public function getSettings(): ?AiSetting
    {
        if (! $this->settingsTableExists()) {
            return null;
        }

        return AiSetting::query()->find(1);
    }

    public function hasStoredOpenAiKey(): bool
    {
        return filled($this->getSettings()?->openai_api_key);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function save(array $data): AiSetting
    {
        $settings = $this->settingsTableExists()
            ? AiSetting::query()->firstOrNew(['id' => 1])
            : new AiSetting(['id' => 1]);

        $settings->forceFill([
            'default_provider' => $this->normalizeProvider((string) Arr::get($data, 'default_provider', config('ai.provider', 'ollama'))),
            'ollama_base_url' => $this->normalizeBaseUrl(Arr::get($data, 'ollama_base_url')),
            'ollama_model' => $this->normalizeNullableString(Arr::get($data, 'ollama_model')),
            'ollama_timeout' => $this->normalizeNullableInt(Arr::get($data, 'ollama_timeout')),
            'openai_base_url' => $this->normalizeBaseUrl(Arr::get($data, 'openai_base_url')),
            'openai_model' => $this->normalizeNullableString(Arr::get($data, 'openai_model')),
            'openai_timeout' => $this->normalizeNullableInt(Arr::get($data, 'openai_timeout')),
        ]);

        if (array_key_exists('openai_api_key', $data) && filled($data['openai_api_key'])) {
            $settings->openai_api_key = trim((string) $data['openai_api_key']);
        }

        if (Arr::get($data, 'clear_openai_api_key') === true) {
            $settings->openai_api_key = null;
        }

        $settings->save();

        $this->applyConfigOverrides($settings);

        return $settings;
    }

    public function applyConfigOverrides(?AiSetting $settings = null): void
    {
        $settings ??= $this->getSettings();

        if (! $settings) {
            return;
        }

        $defaultProvider = $this->normalizeProvider((string) $settings->default_provider);
        config()->set('ai.provider', $defaultProvider);

        $this->applyProviderOverrides('ollama', [
            'base_url' => $settings->ollama_base_url,
            'model' => $settings->ollama_model,
            'timeout' => $settings->ollama_timeout,
        ]);

        $this->applyProviderOverrides('openai', [
            'base_url' => $settings->openai_base_url,
            'api_key' => $settings->openai_api_key,
            'model' => $settings->openai_model,
            'timeout' => $settings->openai_timeout,
        ]);
    }

    /**
     * @param  array<string, mixed>  $overrides
     */
    private function applyProviderOverrides(string $provider, array $overrides): void
    {
        foreach ($overrides as $key => $value) {
            if (is_string($value)) {
                $value = trim($value);
            }

            if ($value === null || $value === '') {
                continue;
            }

            config()->set("ai.providers.{$provider}.{$key}", $value);
        }
    }

    private function normalizeProvider(string $provider): string
    {
        return array_key_exists($provider, $this->providerOptions()) ? $provider : 'ollama';
    }

    private function normalizeBaseUrl(mixed $value): ?string
    {
        $url = $this->normalizeNullableString($value);

        if ($url === null) {
            return null;
        }

        $scheme = strtolower((string) parse_url($url, PHP_URL_SCHEME));

        if (! in_array($scheme, ['http', 'https'], true)) {
            return null;
        }

        return $url;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private function normalizeNullableInt(mixed $value): ?int
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (! is_numeric($value)) {
            return null;
        }

        $normalized = (int) $value;

        return $normalized > 0 ? $normalized : null;
    }

    private function settingsTableExists(): bool
    {
        try {
            return Schema::hasTable('ai_settings');
        } catch (Throwable) {
            return false;
        }
    }
}
