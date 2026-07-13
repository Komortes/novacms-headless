<?php

namespace App\Services;

use App\AI\AiProviderFactory;
use App\AI\Exceptions\AiProviderException;
use App\Models\Content;
use App\Models\ContentEmbedding;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class ContentEmbeddingGenerator
{
    public function __construct(
        private readonly AiProviderFactory $providerFactory,
    ) {}

    /**
     * @return array{chunks: int, deleted: int, provider: string, model: string, dimensions: int, stale: bool}
     */
    public function generateForContent(Content $content, ?string $provider = null, ?string $model = null): array
    {
        $resolvedProvider = $this->resolveProvider($provider);
        $resolvedModel = $this->resolveModel($resolvedProvider, $model);
        $chunks = $this->splitBodyIntoChunks((string) $content->body);
        $providerClient = $this->providerFactory->make($resolvedProvider);
        $expectedDimensions = max(1, (int) config('ai.embeddings.dimensions', 768));
        $preparedEmbeddings = [];
        $dimensions = 0;

        foreach ($chunks as $index => $chunk) {
            $vector = $providerClient->embed($chunk, ['model' => $resolvedModel]);

            if ($vector === []) {
                throw new AiProviderException("Embedding provider returned an empty vector for chunk [{$index}].");
            }

            $dimensions = count($vector);

            if ($dimensions !== $expectedDimensions) {
                throw new AiProviderException(
                    "Embedding model [{$resolvedModel}] returned {$dimensions} dimensions; configured storage expects {$expectedDimensions}.",
                );
            }

            $preparedEmbeddings[] = [
                'chunk_index' => $index,
                'embedding' => $this->prepareEmbeddingValue($vector),
                'meta' => [
                    'chunk_chars' => Str::length($chunk),
                ],
            ];
        }

        $replacement = DB::transaction(function () use ($content, $resolvedProvider, $resolvedModel, $dimensions, $preparedEmbeddings): ?int {
            $currentContent = Content::query()->lockForUpdate()->find($content->id);

            if (! $currentContent || $currentContent->content_hash !== $content->content_hash) {
                return null;
            }

            $deleted = ContentEmbedding::query()
                ->where('content_id', $content->id)
                ->where('source', 'body')
                ->delete();

            foreach ($preparedEmbeddings as $preparedEmbedding) {
                ContentEmbedding::query()->create([
                    'content_id' => $content->id,
                    'source' => 'body',
                    'chunk_index' => $preparedEmbedding['chunk_index'],
                    'content_hash' => $content->content_hash,
                    'provider' => $resolvedProvider,
                    'model' => $resolvedModel,
                    'dimensions' => $dimensions,
                    'embedding' => $preparedEmbedding['embedding'],
                    'meta' => $preparedEmbedding['meta'],
                ]);
            }

            return $deleted;
        });

        return [
            'chunks' => $replacement === null ? 0 : count($preparedEmbeddings),
            'deleted' => $replacement ?? 0,
            'provider' => $resolvedProvider,
            'model' => $resolvedModel,
            'dimensions' => $dimensions,
            'stale' => $replacement === null,
        ];
    }

    /**
     * @return list<string>
     */
    private function splitBodyIntoChunks(string $body): array
    {
        $normalized = trim(str_replace("\r\n", "\n", $body));

        if ($normalized === '') {
            return [];
        }

        $chunkChars = max(250, (int) config('ai.embeddings.chunk_chars', 1200));
        $maxChunks = max(1, (int) config('ai.embeddings.max_chunks', 32));
        $segments = preg_split('/\n{2,}/', $normalized) ?: [$normalized];
        $chunks = [];
        $current = '';

        foreach ($segments as $segment) {
            $segment = trim((string) $segment);
            if ($segment === '') {
                continue;
            }

            $candidate = $current === '' ? $segment : $current."\n\n".$segment;
            if (Str::length($candidate) <= $chunkChars) {
                $current = $candidate;

                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';

                if (count($chunks) >= $maxChunks) {
                    return $chunks;
                }
            }

            foreach ($this->splitLongSegment($segment, $chunkChars) as $piece) {
                $chunks[] = $piece;

                if (count($chunks) >= $maxChunks) {
                    return $chunks;
                }
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        return array_slice($chunks, 0, $maxChunks);
    }

    /**
     * @return list<string>
     */
    private function splitLongSegment(string $segment, int $chunkChars): array
    {
        $parts = [];
        $length = Str::length($segment);
        $offset = 0;

        while ($offset < $length) {
            $parts[] = trim((string) Str::substr($segment, $offset, $chunkChars));
            $offset += $chunkChars;
        }

        return array_values(array_filter($parts, fn (string $item): bool => $item !== ''));
    }

    private function resolveProvider(?string $provider): string
    {
        $resolved = trim((string) ($provider ?: config('ai.embeddings.provider', config('ai.provider', 'ollama'))));

        return $resolved !== '' ? $resolved : 'ollama';
    }

    private function resolveModel(string $provider, ?string $model): string
    {
        $resolved = trim((string) ($model ?: config('ai.embeddings.model', '')));

        if ($resolved !== '') {
            return $resolved;
        }

        $providerModel = trim((string) config("ai.providers.{$provider}.model", ''));

        return $providerModel !== '' ? $providerModel : 'nomic-embed-text';
    }

    /**
     * @param  list<float>  $vector
     */
    private function prepareEmbeddingValue(array $vector): array|string
    {
        if (Schema::getConnection()->getDriverName() !== 'pgsql') {
            return $vector;
        }

        $parts = array_map(
            fn (float $value): string => rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.'),
            $vector,
        );

        return '['.implode(',', $parts).']';
    }
}
