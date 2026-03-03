<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentEmbeddingGenerator;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class GenerateContentEmbeddingsJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $contentId,
        public readonly string $contentHash,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
    ) {
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
}
