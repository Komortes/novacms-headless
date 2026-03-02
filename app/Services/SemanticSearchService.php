<?php

namespace App\Services;

use App\AI\AiProviderFactory;
use App\Models\Content;
use App\Models\ContentEmbedding;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class SemanticSearchService
{
    public function __construct(
        private readonly AiProviderFactory $providerFactory,
    ) {
    }

    /**
     * @return list<array{content: Content, score: float}>
     */
    public function semanticSearch(string $query, int $limit = 10, ?string $locale = null): array
    {
        $normalizedQuery = trim($query);

        if ($normalizedQuery === '') {
            return [];
        }

        $vector = $this->embedText($normalizedQuery);

        if ($vector === []) {
            return [];
        }

        return $this->searchByVector($vector, $limit, $locale);
    }

    /**
     * @return list<array{content: Content, score: float}>
     */
    public function relatedContent(int $contentId, int $limit = 5): array
    {
        $centroid = $this->resolveContentCentroidVector($contentId);

        if ($centroid === []) {
            return [];
        }

        return $this->searchByVector($centroid, $limit, null, $contentId);
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<array{content: Content, score: float}>
     */
    private function searchByVector(array $queryVector, int $limit, ?string $locale = null, ?int $excludeContentId = null): array
    {
        $safeLimit = max(1, min(100, $limit));

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            return $this->searchByVectorPgsql($queryVector, $safeLimit, $locale, $excludeContentId);
        }

        return $this->searchByVectorFallback($queryVector, $safeLimit, $locale, $excludeContentId);
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<array{content: Content, score: float}>
     */
    private function searchByVectorPgsql(array $queryVector, int $limit, ?string $locale, ?int $excludeContentId): array
    {
        $vectorLiteral = $this->vectorToSqlLiteral($queryVector);

        $rows = DB::table('content_embeddings')
            ->join('contents', 'contents.id', '=', 'content_embeddings.content_id')
            ->selectRaw(
                'content_embeddings.content_id, MAX(1 - (content_embeddings.embedding <=> ?::vector)) as score',
                [$vectorLiteral],
            )
            ->when(
                is_string($locale) && $locale !== '',
                fn ($query) => $query->where('contents.locale', $locale),
            )
            ->when(
                is_numeric($excludeContentId),
                fn ($query) => $query->where('content_embeddings.content_id', '!=', $excludeContentId),
            )
            ->groupBy('content_embeddings.content_id')
            ->orderByDesc('score')
            ->limit($limit)
            ->get();

        return $this->rowsToResults($rows);
    }

    /**
     * @param  list<float>  $queryVector
     * @return list<array{content: Content, score: float}>
     */
    private function searchByVectorFallback(array $queryVector, int $limit, ?string $locale, ?int $excludeContentId): array
    {
        $rows = ContentEmbedding::query()
            ->with('content')
            ->when(
                is_string($locale) && $locale !== '',
                fn ($query) => $query->whereHas('content', fn ($contentQuery) => $contentQuery->where('locale', $locale)),
            )
            ->when(
                is_numeric($excludeContentId),
                fn ($query) => $query->where('content_id', '!=', $excludeContentId),
            )
            ->get();

        $bestByContent = [];

        foreach ($rows as $row) {
            $candidateVector = $this->normalizeVector($row->embedding);

            if ($candidateVector === []) {
                continue;
            }

            $score = $this->cosineSimilarity($queryVector, $candidateVector);

            if (! isset($bestByContent[$row->content_id]) || $score > $bestByContent[$row->content_id]['score']) {
                $bestByContent[$row->content_id] = [
                    'content' => $row->content,
                    'score' => $score,
                ];
            }
        }

        return collect($bestByContent)
            ->sortByDesc('score')
            ->take($limit)
            ->filter(fn (array $item): bool => $item['content'] instanceof Content)
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, object>  $rows
     * @return list<array{content: Content, score: float}>
     */
    private function rowsToResults(Collection $rows): array
    {
        $contentIds = $rows->pluck('content_id')->map(fn ($id): int => (int) $id)->all();
        $contents = Content::query()
            ->whereIn('id', $contentIds)
            ->get()
            ->keyBy('id');

        return $rows
            ->map(function (object $row) use ($contents): ?array {
                $contentId = (int) $row->content_id;
                $content = $contents->get($contentId);

                if (! $content instanceof Content) {
                    return null;
                }

                return [
                    'content' => $content,
                    'score' => (float) $row->score,
                ];
            })
            ->filter()
            ->values()
            ->all();
    }

    /**
     * @return list<float>
     */
    private function resolveContentCentroidVector(int $contentId): array
    {
        $vectors = [];

        if (Schema::getConnection()->getDriverName() === 'pgsql') {
            $rawVectors = DB::table('content_embeddings')
                ->where('content_id', $contentId)
                ->selectRaw('embedding::text as embedding_text')
                ->pluck('embedding_text');

            foreach ($rawVectors as $rawVector) {
                $normalized = $this->normalizeVector($rawVector);

                if ($normalized !== []) {
                    $vectors[] = $normalized;
                }
            }
        } else {
            $rawVectors = ContentEmbedding::query()
                ->where('content_id', $contentId)
                ->pluck('embedding');

            foreach ($rawVectors as $rawVector) {
                $normalized = $this->normalizeVector($rawVector);

                if ($normalized !== []) {
                    $vectors[] = $normalized;
                }
            }
        }

        if ($vectors === []) {
            return [];
        }

        return $this->averageVectors($vectors);
    }

    /**
     * @return list<float>
     */
    private function embedText(string $text): array
    {
        $provider = (string) config('ai.embeddings.provider', config('ai.provider', 'ollama'));
        $model = (string) config('ai.embeddings.model', '');
        $options = [];

        if ($model !== '') {
            $options['model'] = $model;
        }

        return $this->providerFactory->make($provider)->embed($text, $options);
    }

    /**
     * @param  list<float>  $vector
     */
    private function vectorToSqlLiteral(array $vector): string
    {
        return '['.implode(',', array_map(
            fn (float $value): string => rtrim(rtrim(number_format($value, 8, '.', ''), '0'), '.'),
            $vector,
        )).']';
    }

    /**
     * @param  mixed  $rawVector
     * @return list<float>
     */
    private function normalizeVector(mixed $rawVector): array
    {
        if (is_string($rawVector)) {
            $trimmed = trim($rawVector);
            $trimmed = trim($trimmed, '[]');

            if ($trimmed === '') {
                return [];
            }

            $rawVector = explode(',', $trimmed);
        }

        if (! is_array($rawVector)) {
            return [];
        }

        $vector = [];

        foreach ($rawVector as $value) {
            if (! is_numeric($value)) {
                continue;
            }

            $vector[] = (float) $value;
        }

        return $vector;
    }

    /**
     * @param  list<list<float>>  $vectors
     * @return list<float>
     */
    private function averageVectors(array $vectors): array
    {
        $size = min(array_map(fn (array $vector): int => count($vector), $vectors));

        if ($size <= 0) {
            return [];
        }

        $sums = array_fill(0, $size, 0.0);

        foreach ($vectors as $vector) {
            for ($index = 0; $index < $size; $index++) {
                $sums[$index] += $vector[$index];
            }
        }

        $count = count($vectors);

        return array_map(fn (float $sum): float => $sum / $count, $sums);
    }

    /**
     * @param  list<float>  $a
     * @param  list<float>  $b
     */
    private function cosineSimilarity(array $a, array $b): float
    {
        $size = min(count($a), count($b));

        if ($size === 0) {
            return 0.0;
        }

        $dot = 0.0;
        $normA = 0.0;
        $normB = 0.0;

        for ($index = 0; $index < $size; $index++) {
            $dot += $a[$index] * $b[$index];
            $normA += $a[$index] ** 2;
            $normB += $b[$index] ** 2;
        }

        if ($normA <= 0.0 || $normB <= 0.0) {
            return 0.0;
        }

        return $dot / (sqrt($normA) * sqrt($normB));
    }
}
