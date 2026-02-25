<?php

namespace Tests\Unit\AI;

use App\AI\Exceptions\AiProviderException;
use App\AI\Providers\OllamaProvider;
use Illuminate\Http\Client\Factory as HttpFactory;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OllamaProviderTest extends TestCase
{
    public function test_generate_maps_ollama_response_into_result_dto(): void
    {
        Http::fake([
            'http://ollama.local/api/generate' => Http::response([
                'model' => 'llama3.1',
                'response' => '{"summary_tldr":"hello"}',
                'prompt_eval_count' => 42,
                'eval_count' => 17,
            ], 200),
        ]);

        $provider = new OllamaProvider(
            app(HttpFactory::class),
            'http://ollama.local',
            'llama3.1',
            10,
        );

        $result = $provider->generate('Summarize this', [
            'format' => 'json',
            'options' => ['temperature' => 0.2],
        ]);

        $this->assertSame('{"summary_tldr":"hello"}', $result->text);
        $this->assertSame('llama3.1', $result->model);
        $this->assertSame(42, $result->tokensIn);
        $this->assertSame(17, $result->tokensOut);

        Http::assertSent(function (Request $request): bool {
            $body = $request->data();

            return $request->url() === 'http://ollama.local/api/generate'
                && $body['model'] === 'llama3.1'
                && $body['prompt'] === 'Summarize this'
                && $body['stream'] === false
                && $body['format'] === 'json';
        });
    }

    public function test_generate_throws_when_response_payload_is_invalid(): void
    {
        Http::fake([
            'http://ollama.local/api/generate' => Http::response([
                'model' => 'llama3.1',
            ], 200),
        ]);

        $provider = new OllamaProvider(
            app(HttpFactory::class),
            'http://ollama.local',
            'llama3.1',
            10,
        );

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('missing "response" field');

        $provider->generate('Summarize this');
    }
}

