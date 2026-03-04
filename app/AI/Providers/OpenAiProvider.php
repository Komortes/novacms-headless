<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class OpenAiProvider implements AiProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUrl,
        private readonly ?string $apiKey,
        private readonly string $defaultModel,
        private readonly int $timeout,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $prompt, array $options = []): AiGenerationResult
    {
        if (blank($this->apiKey)) {
            throw new AiProviderException('OPENAI_API_KEY is not configured.');
        }

        $model = (string) ($options['model'] ?? $this->defaultModel);
        $body = [
            'model' => $model,
            'input' => $prompt,
        ];

        if (isset($options['instructions'])) {
            $body['instructions'] = $options['instructions'];
        }

        if (isset($options['temperature'])) {
            $body['temperature'] = $options['temperature'];
        }

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->baseUrl, '/'))
                ->timeout($this->timeout)
                ->retry(
                    (int) config('ai.http_retry.times', 2),
                    (int) config('ai.http_retry.sleep_ms', 250),
                )
                ->acceptJson()
                ->withToken($this->apiKey)
                ->post('/responses', $body)
                ->throw();
        } catch (RequestException|ConnectionException $exception) {
            throw new AiProviderException(
                'OpenAI request failed: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $data = $response->json();
        $text = (string) data_get($data, 'output_text', '');

        if ($text === '') {
            $outputTextItem = collect((array) data_get($data, 'output', []))
                ->flatMap(fn ($item) => (array) data_get($item, 'content', []))
                ->firstWhere('type', 'output_text');

            $text = (string) data_get($outputTextItem, 'text', '');
        }

        if ($text === '') {
            throw new AiProviderException('OpenAI response does not contain output text.');
        }

        return new AiGenerationResult(
            text: $text,
            model: (string) data_get($data, 'model', $model),
            tokensIn: data_get($data, 'usage.input_tokens') !== null ? (int) data_get($data, 'usage.input_tokens') : null,
            tokensOut: data_get($data, 'usage.output_tokens') !== null ? (int) data_get($data, 'usage.output_tokens') : null,
            raw: is_array($data) ? $data : [],
        );
    }

    /**
     * @param  array<string, mixed>  $options
     * @return list<float>
     */
    public function embed(string $input, array $options = []): array
    {
        if (blank($this->apiKey)) {
            throw new AiProviderException('OPENAI_API_KEY is not configured.');
        }

        $model = (string) ($options['model'] ?? config('ai.embeddings.model', $this->defaultModel));
        $body = [
            'model' => $model,
            'input' => $input,
        ];

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->baseUrl, '/'))
                ->timeout($this->timeout)
                ->retry(
                    (int) config('ai.http_retry.times', 2),
                    (int) config('ai.http_retry.sleep_ms', 250),
                )
                ->acceptJson()
                ->withToken($this->apiKey)
                ->post('/embeddings', $body)
                ->throw();
        } catch (RequestException|ConnectionException $exception) {
            throw new AiProviderException(
                'OpenAI embedding request failed: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $data = $response->json();
        $candidate = data_get($data, 'data.0.embedding');

        if (! is_array($candidate)) {
            throw new AiProviderException('OpenAI embedding response is missing data[0].embedding.');
        }

        $vector = [];

        foreach ($candidate as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $vector[] = (float) $value;
        }

        if ($vector === []) {
            throw new AiProviderException('OpenAI embedding response is empty.');
        }

        return $vector;
    }
}
