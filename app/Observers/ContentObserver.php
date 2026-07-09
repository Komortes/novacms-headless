<?php

namespace App\Observers;

use App\DomainEvents;
use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\ContentEmbeddingDispatcher;
use App\Services\ContentPublishQualityGate;
use App\Services\ContentSummaryDispatcher;
use App\Services\DomainEventPublisher;

class ContentObserver
{
    public function saving(Content $content): void
    {
        $content->content_hash = $content->generateContentHash();
    }

    public function created(Content $content): void
    {
        $this->markSummaryAsPending($content);
        $this->dispatchSummary($content);
        $this->dispatchEmbeddings($content);
    }

    public function updated(Content $content): void
    {
        if ($content->wasChanged('content_hash')) {
            $this->markSummaryAsPending($content);
            $this->dispatchSummary($content);
            $this->dispatchEmbeddings($content);
        }

        // Status transitions (draft -> published -> archived) do not change the
        // content hash but headless consumers still need to hear about them.
        if ($content->wasChanged('content_hash') || $content->wasChanged('status')) {
            $this->publishContentUpdated($content);
        }
    }

    public function updating(Content $content): void
    {
        if (! $content->isDirty('status')) {
            return;
        }

        $targetStatus = $content->status instanceof ContentStatus
            ? $content->status
            : ContentStatus::tryFrom((string) $content->status);

        if ($targetStatus !== ContentStatus::PUBLISHED) {
            return;
        }

        app(ContentPublishQualityGate::class)->assertCanPublish($content);
    }

    private function markSummaryAsPending(Content $content): void
    {
        $content->summary()->updateOrCreate(
            ['content_id' => $content->id],
            [
                'summary_tldr' => null,
                'summary_bullets' => null,
                'summary_meta_description' => null,
                'summary_faq' => null,
                'summary_tags' => null,
                'status' => SummaryStatus::PENDING,
                'model' => null,
                'prompt_version' => null,
                'tokens_in' => null,
                'tokens_out' => null,
                'generation_ms' => null,
                'last_error' => null,
            ],
        );
    }

    private function dispatchEmbeddings(Content $content): void
    {
        if (! (bool) config('ai.embeddings.auto_dispatch', true)) {
            return;
        }

        app(ContentEmbeddingDispatcher::class)->dispatch($content);
    }

    private function dispatchSummary(Content $content): void
    {
        if (! (bool) config('ai.summary.auto_dispatch', true)) {
            return;
        }

        app(ContentSummaryDispatcher::class)->dispatch($content);
    }

    private function publishContentUpdated(Content $content): void
    {
        app(DomainEventPublisher::class)->publish(DomainEvents::CONTENT_UPDATED, [
            'content_id' => $content->id,
            'slug' => $content->slug,
            'locale' => $content->locale,
            'status' => $content->status instanceof ContentStatus ? $content->status->value : (string) $content->status,
            'content_hash' => $content->content_hash,
        ]);
    }
}
