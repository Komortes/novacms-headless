<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use App\Models\ContentAiSummaryEvent;
use App\Services\ContentSummaryDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentSummaryDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_sets_pending_status_and_queues_job(): void
    {
        Queue::fake();

        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'dispatch-test',
            'title' => 'Dispatch Test',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        app(ContentSummaryDispatcher::class)->dispatch(
            content: $content,
            provider: 'ollama',
            model: 'qwen2.5:1.5b',
        );

        $summary = $content->summary()->first();

        $this->assertNotNull($summary);
        $this->assertSame(SummaryStatus::PENDING, $summary?->status);
        $this->assertNull($summary?->last_error);
        $this->assertDatabaseHas('content_ai_summary_events', [
            'content_id' => $content->id,
            'event' => 'queued',
            'provider' => 'ollama',
            'model' => 'qwen2.5:1.5b',
        ]);

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id
                && $job->provider === 'ollama'
                && $job->model === 'qwen2.5:1.5b'
                && $job->version > 0;
        });
    }

    public function test_it_can_cancel_pending_generation(): void
    {
        Queue::fake();

        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'dispatch-cancel-test',
            'title' => 'Dispatch Cancel Test',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $dispatcher = app(ContentSummaryDispatcher::class);

        $dispatcher->dispatch(
            content: $content,
            provider: 'ollama',
            model: 'qwen2.5:1.5b',
        );

        $dispatcher->cancelPending($content);

        $summary = $content->summary()->first();

        $this->assertNotNull($summary);
        $this->assertSame(SummaryStatus::FAILED, $summary?->status);
        $this->assertSame('Queued generation cancelled by user.', $summary?->last_error);

        $latestEvent = ContentAiSummaryEvent::query()
            ->where('content_id', $content->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($latestEvent);
        $this->assertSame('cancelled', $latestEvent?->event);
    }
}
