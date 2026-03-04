<?php

namespace App\Services;

use App\Jobs\GenerateContentEmbeddingsJob;
use App\Models\Content;

class ContentEmbeddingDispatcher
{
    public function dispatch(Content $content, ?string $provider = null, ?string $model = null): void
    {
        GenerateContentEmbeddingsJob::dispatch(
            contentId: $content->id,
            contentHash: $content->content_hash,
            provider: $provider,
            model: $model,
        )->onQueue((string) config('ai.jobs.embeddings.queue', 'ai'));
    }
}
