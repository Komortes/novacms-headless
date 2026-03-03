<?php

namespace App\Services;

use App\Models\Content;
use App\Models\ContentEmbeddingEvent;
use Carbon\CarbonInterface;

class ContentEmbeddingEventLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Content $content,
        string $event,
        ?string $provider = null,
        ?string $model = null,
        ?string $contentHash = null,
        ?int $chunks = null,
        ?int $dimensions = null,
        ?string $message = null,
        array $meta = [],
        ?CarbonInterface $createdAt = null,
    ): ContentEmbeddingEvent {
        return ContentEmbeddingEvent::query()->create([
            'content_id' => $content->id,
            'event' => $event,
            'provider' => $provider,
            'model' => $model,
            'content_hash' => $contentHash,
            'chunks' => $chunks,
            'dimensions' => $dimensions,
            'message' => $message,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
