<?php

namespace App\Observers;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\ContentPublishQualityGate;

class ContentObserver
{
    public function saving(Content $content): void
    {
        $content->content_hash = $content->generateContentHash();
    }

    public function created(Content $content): void
    {
        $this->markSummaryAsPending($content);
    }

    public function updated(Content $content): void
    {
        if (! $content->wasChanged('content_hash')) {
            return;
        }

        $this->markSummaryAsPending($content);
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
}
