<?php

namespace App\Jobs;

use App\DomainEvents;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentEmbeddingEventLogger;
use App\Services\ContentEmbeddingGenerator;
use App\Services\DomainEventPublisher;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Str;
use Throwable;

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
        ContentEmbeddingEventLogger $eventLogger,
        DomainEventPublisher $domainEventPublisher,
    ): void
    {
        $content = Content::query()->find($this->contentId);

        if (! $content) {
            return;
        }

        $eventLogger->record(
            content: $content,
            event: 'started',
            provider: $this->provider,
            model: $this->model,
            contentHash: $content->content_hash,
        );

        $domainEventPublisher->publish(DomainEvents::EMBEDDING_STATUS_CHANGED, [
            'content_id' => $content->id,
            'status' => 'started',
            'provider' => $this->provider,
            'model' => $this->model,
            'content_hash' => $content->content_hash,
        ]);

        // Skip outdated jobs that were queued before content was updated.
        if ($content->content_hash !== $this->contentHash) {
            $eventLogger->record(
                content: $content,
                event: 'skipped',
                provider: $this->provider,
                model: $this->model,
                contentHash: $content->content_hash,
                message: 'Outdated content hash; skipped job.',
            );

            $domainEventPublisher->publish(DomainEvents::EMBEDDING_STATUS_CHANGED, [
                'content_id' => $content->id,
                'status' => 'skipped',
                'provider' => $this->provider,
                'model' => $this->model,
                'content_hash' => $content->content_hash,
                'reason' => 'outdated_hash',
            ]);

            return;
        }

        $aiSettingsManager->applyConfigOverrides();

        try {
            $result = $generator->generateForContent(
                content: $content,
                provider: $this->provider,
                model: $this->model,
            );

            $eventLogger->record(
                content: $content,
                event: 'completed',
                provider: $result['provider'],
                model: $result['model'],
                contentHash: $content->content_hash,
                chunks: $result['chunks'],
                dimensions: $result['dimensions'],
                meta: [
                    'deleted' => $result['deleted'],
                ],
            );

            $domainEventPublisher->publish(DomainEvents::EMBEDDING_CREATED, [
                'content_id' => $content->id,
                'content_hash' => $content->content_hash,
                'provider' => $result['provider'],
                'model' => $result['model'],
                'chunks' => $result['chunks'],
                'dimensions' => $result['dimensions'],
                'deleted' => $result['deleted'],
            ]);

            $domainEventPublisher->publish(DomainEvents::EMBEDDING_STATUS_CHANGED, [
                'content_id' => $content->id,
                'status' => 'completed',
                'provider' => $result['provider'],
                'model' => $result['model'],
                'content_hash' => $content->content_hash,
                'chunks' => $result['chunks'],
                'dimensions' => $result['dimensions'],
            ]);
        } catch (Throwable $exception) {
            $eventLogger->record(
                content: $content,
                event: 'failed',
                provider: $this->provider,
                model: $this->model,
                contentHash: $content->content_hash,
                message: Str::limit($exception->getMessage(), 500),
            );

            $domainEventPublisher->publish(DomainEvents::EMBEDDING_STATUS_CHANGED, [
                'content_id' => $content->id,
                'status' => 'failed',
                'provider' => $this->provider,
                'model' => $this->model,
                'content_hash' => $content->content_hash,
                'error' => Str::limit($exception->getMessage(), 500),
            ]);

            throw $exception;
        }
    }
}
