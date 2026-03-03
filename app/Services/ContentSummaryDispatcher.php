<?php

namespace App\Services;

use App\DomainEvents;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;

class ContentSummaryDispatcher
{
    public function __construct(
        private readonly ContentSummaryQueueVersion $queueVersion,
        private readonly ContentSummaryEventLogger $eventLogger,
        private readonly DomainEventPublisher $domainEventPublisher,
    ) {
    }

    public function dispatch(Content $content, ?string $provider = null, ?string $model = null): void
    {
        $summary = $content->summary()->firstOrCreate(
            ['content_id' => $content->id],
            ['status' => SummaryStatus::PENDING],
        );

        $summary->forceFill([
            'status' => SummaryStatus::PENDING,
            'last_error' => null,
        ])->save();

        $version = $this->queueVersion->nextForDispatch($content->id);

        $this->eventLogger->record(
            content: $content,
            event: 'queued',
            summary: $summary,
            provider: $provider,
            model: $model,
            queueVersion: $version,
        );

        $this->domainEventPublisher->publish(DomainEvents::SUMMARY_STATUS_CHANGED, [
            'content_id' => $content->id,
            'summary_id' => $summary->id,
            'status' => SummaryStatus::PENDING->value,
            'provider' => $provider,
            'model' => $model,
            'queue_version' => $version,
        ]);

        GenerateContentSummaryJob::dispatch(
            contentId: $content->id,
            provider: $provider,
            model: $model,
            version: $version,
        );
    }

    public function cancelPending(Content $content): void
    {
        $this->queueVersion->cancelPending($content->id);

        $content->summary()->updateOrCreate(
            ['content_id' => $content->id],
            [
                'status' => SummaryStatus::FAILED,
                'last_error' => 'Queued generation cancelled by user.',
            ],
        );

        $this->eventLogger->record(
            content: $content,
            event: 'cancelled',
            summary: $content->summary()->first(),
            queueVersion: $this->queueVersion->current($content->id),
            message: 'Queued generation cancelled by user.',
        );

        $this->domainEventPublisher->publish(DomainEvents::SUMMARY_STATUS_CHANGED, [
            'content_id' => $content->id,
            'summary_id' => $content->summary()->value('id'),
            'status' => SummaryStatus::FAILED->value,
            'reason' => 'cancelled',
        ]);
    }
}
