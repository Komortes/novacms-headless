<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentEmbeddingGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Throwable;

class GenerateContentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(
        public readonly int $contentId,
        public readonly string $contentHash,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
    ) {
        $this->tries = max(1, (int) config('ai.jobs.embeddings.tries', 3));
        $this->timeout = max(30, (int) config('ai.jobs.embeddings.timeout', 300));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('embeddings-content-'.$this->contentId))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 30),
            new RateLimited('ai-requests'),
        ];
    }

    public function backoff(): int
    {
        return max(1, (int) config('ai.jobs.embeddings.backoff_seconds', 15));
    }

    public function handle(
        ContentEmbeddingGenerator $generator,
        AiSettingsManager $aiSettingsManager,
    ): void
    {
        $content = Content::query()->find($this->contentId);

        if (! $content) {
            return;
        }

        // Skip outdated jobs that were queued before content was updated.
        if ($content->content_hash !== $this->contentHash) {
            return;
        }

        $aiSettingsManager->applyConfigOverrides();

        $generator->generateForContent(
            content: $content,
            provider: $this->provider,
            model: $this->model,
        );
    }

    public function failed(Throwable $exception): void
    {
        Log::warning('Embeddings job exhausted retries.', [
            'content_id' => $this->contentId,
            'provider' => $this->provider,
            'model' => $this->model,
            'error' => $exception->getMessage(),
        ]);
    }
}
