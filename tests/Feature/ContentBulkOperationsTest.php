<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentEmbeddingsJob;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use App\Models\ContentAiSummaryEvent;
use App\Services\ContentBulkOperations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentBulkOperationsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
    }

    public function test_it_bulk_queues_summaries_and_skips_generating_records(): void
    {
        Queue::fake();

        $failed = $this->makeContent('bulk-failed');
        $failedSummary = $failed->summary()->updateOrCreate(['content_id' => $failed->id], [
            'status' => SummaryStatus::FAILED,
            'model' => 'qwen2.5:1.5b',
            'last_error' => 'provider timeout',
        ]);
        ContentAiSummaryEvent::query()->create([
            'content_id' => $failed->id,
            'content_ai_summary_id' => $failedSummary->id,
            'event' => 'failed',
            'provider' => 'ollama',
            'model' => 'qwen2.5:1.5b',
            'created_at' => now(),
        ]);

        $generating = $this->makeContent('bulk-generating');
        $generating->summary()->updateOrCreate(['content_id' => $generating->id], [
            'status' => SummaryStatus::GENERATING,
        ]);

        $fresh = $this->makeContent('bulk-fresh');

        $result = app(ContentBulkOperations::class)->queueSummaries([
            $failed->fresh('summary'),
            $generating->fresh('summary'),
            $fresh->fresh('summary'),
        ]);

        $this->assertSame([
            'queued' => 2,
            'skipped' => 1,
        ], $result);

        Queue::assertPushed(GenerateContentSummaryJob::class, 2);
        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($failed): bool {
            return $job->contentId === $failed->id
                && $job->provider === 'ollama'
                && $job->model === 'qwen2.5:1.5b';
        });
        Queue::assertPushed(GenerateContentSummaryJob::class, fn (GenerateContentSummaryJob $job): bool => $job->contentId === $fresh->id);
    }

    public function test_it_retries_only_failed_summaries_with_latest_context(): void
    {
        Queue::fake();

        $failed = $this->makeContent('retry-failed');
        $failedSummary = $failed->summary()->updateOrCreate(['content_id' => $failed->id], [
            'status' => SummaryStatus::FAILED,
            'model' => 'gpt-4.1-mini',
            'last_error' => 'api error',
        ]);
        ContentAiSummaryEvent::query()->create([
            'content_id' => $failed->id,
            'content_ai_summary_id' => $failedSummary->id,
            'event' => 'failed',
            'provider' => 'openai',
            'model' => 'gpt-4.1-mini',
            'created_at' => now(),
        ]);

        $ready = $this->makeContent('retry-ready');
        $ready->summary()->updateOrCreate(['content_id' => $ready->id], [
            'status' => SummaryStatus::READY,
            'summary_tldr' => 'This summary is long enough to satisfy the publish rule.',
            'summary_bullets' => ['One point', 'Another point'],
            'summary_meta_description' => 'Short meta description for ready summary.',
            'summary_faq' => [['question' => 'What?', 'answer' => 'Answer.']],
            'summary_tags' => ['cms', 'ai'],
        ]);

        $result = app(ContentBulkOperations::class)->retryFailedSummaries([
            $failed->fresh('summary'),
            $ready->fresh('summary'),
        ]);

        $this->assertSame([
            'retried' => 1,
            'skipped' => 1,
        ], $result);

        Queue::assertPushed(GenerateContentSummaryJob::class, 1);
        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($failed): bool {
            return $job->contentId === $failed->id
                && $job->provider === 'openai'
                && $job->model === 'gpt-4.1-mini';
        });
    }

    public function test_it_bulk_queues_embedding_reindex(): void
    {
        Queue::fake();

        $first = $this->makeContent('embed-first');
        $second = $this->makeContent('embed-second');

        $result = app(ContentBulkOperations::class)->queueEmbeddings([
            $first,
            $second,
        ]);

        $this->assertSame(['queued' => 2], $result);

        Queue::assertPushed(GenerateContentEmbeddingsJob::class, 2);
        Queue::assertPushed(GenerateContentEmbeddingsJob::class, fn (GenerateContentEmbeddingsJob $job): bool => $job->contentId === $first->id);
        Queue::assertPushed(GenerateContentEmbeddingsJob::class, fn (GenerateContentEmbeddingsJob $job): bool => $job->contentId === $second->id);
    }

    public function test_it_bulk_updates_statuses_and_reports_publish_gate_failures(): void
    {
        $publishable = $this->makeContent('publishable');
        $publishable->summary()->updateOrCreate(['content_id' => $publishable->id], [
            'status' => SummaryStatus::READY,
            'summary_tldr' => 'This summary is long enough to satisfy the publish rule.',
            'summary_bullets' => ['One point', 'Another point'],
            'summary_meta_description' => 'Short meta description for ready summary.',
            'summary_faq' => [['question' => 'What?', 'answer' => 'Answer.']],
            'summary_tags' => ['cms', 'ai'],
        ]);

        $blocked = $this->makeContent('blocked');

        $result = app(ContentBulkOperations::class)->updateStatuses([
            $publishable->fresh('summary'),
            $blocked->fresh('summary'),
        ], ContentStatus::PUBLISHED);

        $this->assertSame(1, $result['updated']);
        $this->assertSame(0, $result['skipped']);
        $this->assertSame(1, $result['failed']);
        $this->assertNotEmpty($result['errors']);
        $this->assertStringContainsString('AI summary must be in "ready" state', $result['errors'][0]);

        $this->assertSame(ContentStatus::PUBLISHED, $publishable->fresh()->status);
        $this->assertSame(ContentStatus::DRAFT, $blocked->fresh()->status);
    }

    private function makeContent(string $slug): Content
    {
        return Content::query()->create([
            'type' => ContentType::POST,
            'slug' => $slug,
            'title' => ucfirst(str_replace('-', ' ', $slug)),
            'body' => 'Body for '.$slug,
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);
    }
}
