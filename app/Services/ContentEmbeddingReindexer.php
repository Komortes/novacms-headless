<?php

namespace App\Services;

use App\Models\Content;
use Illuminate\Database\Eloquent\Builder;

class ContentEmbeddingReindexer
{
    public const MODE_INCREMENTAL = 'incremental';

    public const MODE_FULL = 'full';

    public function __construct(
        private readonly ContentEmbeddingDispatcher $dispatcher,
        private readonly ContentEmbeddingGenerator $generator,
    ) {
    }

    public function isSupportedMode(string $mode): bool
    {
        return in_array($mode, [self::MODE_INCREMENTAL, self::MODE_FULL], true);
    }

    public function dispatch(string $mode = self::MODE_INCREMENTAL, ?string $provider = null, ?string $model = null): int
    {
        $queued = 0;

        $this->queryForMode($mode)
            ->chunkById(100, function ($contents) use (&$queued, $provider, $model): void {
                foreach ($contents as $content) {
                    $this->dispatcher->dispatch($content, $provider, $model);
                    $queued++;
                }
            });

        return $queued;
    }

    /**
     * @return array{
     *     processed: int,
     *     failed: int,
     *     failures: list<array{content_id: int, slug: string, error: string}>
     * }
     */
    public function runSync(string $mode = self::MODE_INCREMENTAL, ?string $provider = null, ?string $model = null): array
    {
        $processed = 0;
        $failed = 0;
        $failures = [];

        $this->queryForMode($mode)
            ->chunkById(50, function ($contents) use (&$processed, &$failed, &$failures, $provider, $model): void {
                foreach ($contents as $content) {
                    try {
                        $this->generator->generateForContent(
                            content: $content,
                            provider: $provider,
                            model: $model,
                        );

                        $processed++;
                    } catch (\Throwable $exception) {
                        $failed++;
                        $failures[] = [
                            'content_id' => $content->id,
                            'slug' => $content->slug,
                            'error' => $exception->getMessage(),
                        ];
                    }
                }
            });

        return [
            'processed' => $processed,
            'failed' => $failed,
            'failures' => $failures,
        ];
    }

    public function count(string $mode = self::MODE_INCREMENTAL): int
    {
        return $this->queryForMode($mode)->count();
    }

    private function queryForMode(string $mode): Builder
    {
        $query = Content::query()->orderBy('id');

        if ($mode === self::MODE_FULL) {
            return $query;
        }

        return $query->whereNotExists(function ($subquery): void {
            $subquery
                ->selectRaw('1')
                ->from('content_embeddings')
                ->whereColumn('content_embeddings.content_id', 'contents.id')
                ->whereColumn('content_embeddings.content_hash', 'contents.content_hash');
        });
    }
}
