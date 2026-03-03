<?php

namespace App\Services;

use App\DomainEvents;
use App\Jobs\GenerateContentEmbeddingsJob;
use App\Models\Content;

class ContentEmbeddingDispatcher
{
    public function __construct(
        private readonly ContentEmbeddingEventLogger $eventLogger,
        private readonly DomainEventPublisher $domainEventPublisher,
    ) {
    }

    public function dispatch(Content $content, ?string $provider = null, ?string $model = null): void
    {
        $this->eventLogger->record(
            content: $content,
            event: 'queued',
            provider: $provider,
            model: $model,
            contentHash: $content->content_hash,
        );

        $this->domainEventPublisher->publish(DomainEvents::EMBEDDING_STATUS_CHANGED, [
            'content_id' => $content->id,
            'status' => 'queued',
            'provider' => $provider,
            'model' => $model,
            'content_hash' => $content->content_hash,
        ]);

        GenerateContentEmbeddingsJob::dispatch(
            contentId: $content->id,
            contentHash: $content->content_hash,
            provider: $provider,
            model: $model,
        );
    }
}
