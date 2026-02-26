<?php

namespace App\Services;

use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use Carbon\CarbonInterface;

class ContentSummaryEventLogger
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function record(
        Content $content,
        string $event,
        ?ContentAiSummary $summary = null,
        ?string $provider = null,
        ?string $model = null,
        ?int $queueVersion = null,
        ?int $waitMs = null,
        ?int $durationMs = null,
        ?string $message = null,
        array $meta = [],
        ?CarbonInterface $createdAt = null,
    ): ContentAiSummaryEvent {
        return ContentAiSummaryEvent::query()->create([
            'content_id' => $content->id,
            'content_ai_summary_id' => $summary?->id,
            'event' => $event,
            'provider' => $provider,
            'model' => $model,
            'queue_version' => $queueVersion,
            'wait_ms' => $waitMs,
            'duration_ms' => $durationMs,
            'message' => $message,
            'meta' => $meta === [] ? null : $meta,
            'created_at' => $createdAt ?? now(),
        ]);
    }
}
