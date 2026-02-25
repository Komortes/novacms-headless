<?php

namespace App\AI\Providers;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\AI\Exceptions\AiProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\RequestException;

class OllamaProvider implements AiProviderInterface
{
    public function __construct(
        private readonly HttpFactory $http,
        private readonly string $baseUrl,
        private readonly string $defaultModel,
        private readonly int $timeout,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function generate(string $prompt, array $options = []): AiGenerationResult
    {
        $model = (string) ($options['model'] ?? $this->defaultModel);
        $requestBody = [
            'model' => $model,
            'prompt' => $prompt,
            'stream' => false,
        ];

        if (isset($options['format'])) {
            $requestBody['format'] = $options['format'];
        }

        if (isset($options['system'])) {
            $requestBody['system'] = $options['system'];
        }

        if (isset($options['options']) && is_array($options['options'])) {
            $requestBody['options'] = $options['options'];
        }

        try {
            $response = $this->http
                ->baseUrl(rtrim($this->baseUrl, '/'))
                ->timeout($this->timeout)
                ->acceptJson()
                ->post('/api/generate', $requestBody)
                ->throw();
        } catch (RequestException|ConnectionException $exception) {
            if ($exception instanceof RequestException && $exception->response !== null) {
                $status = $exception->response->status();
                $errorMessage = (string) ($exception->response->json('error') ?? '');

                if ($status === 404 && str_contains($errorMessage, 'not found')) {
                    throw new AiProviderException(
                        "Ollama model [{$model}] is not installed. Run: docker compose exec ollama ollama pull {$model}",
                        previous: $exception,
                    );
                }
            }

            throw new AiProviderException(
                'Ollama request failed: '.$exception->getMessage(),
                previous: $exception,
            );
        }

        $data = $response->json();

        if (! is_array($data) || ! isset($data['response'])) {
            throw new AiProviderException('Ollama response is missing "response" field.');
        }

        return new AiGenerationResult(
            text: (string) $data['response'],
            model: (string) ($data['model'] ?? $model),
            tokensIn: isset($data['prompt_eval_count']) ? (int) $data['prompt_eval_count'] : null,
            tokensOut: isset($data['eval_count']) ? (int) $data['eval_count'] : null,
            raw: $data,
        );
    }
}
