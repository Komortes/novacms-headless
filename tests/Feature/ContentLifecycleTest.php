<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_content_create_sets_hash_and_pending_summary(): void
    {
        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'hello-world',
            'title' => 'Hello World',
            'body' => 'Initial body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $content->refresh();
        $summary = $content->summary;

        $this->assertNotNull($content->content_hash);
        $this->assertSame(64, strlen($content->content_hash));
        $this->assertNotNull($summary);
        $this->assertSame(SummaryStatus::PENDING, $summary?->status);
    }

    public function test_content_create_can_auto_dispatch_summary_job(): void
    {
        config()->set('ai.summary.auto_dispatch', true);
        Queue::fake();

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'queued-on-create',
            'title' => 'Queued On Create',
            'body' => 'Initial body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id && $job->version > 0;
        });
    }

    public function test_content_hash_change_resets_summary_to_pending(): void
    {
        $content = Content::create([
            'type' => ContentType::PAGE,
            'slug' => 'about',
            'title' => 'About',
            'body' => 'Version one',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $initialHash = $content->content_hash;

        $content->summary()->update([
            'summary_tldr' => 'Ready summary',
            'status' => SummaryStatus::READY,
            'model' => 'llama3.1',
            'prompt_version' => 'content-summary:v1',
            'tokens_in' => 100,
            'tokens_out' => 20,
        ]);

        $content->update([
            'body' => 'Version two',
        ]);

        $content->refresh();
        $summary = $content->summary()->first();

        $this->assertNotSame($initialHash, $content->content_hash);
        $this->assertSame(SummaryStatus::PENDING, $summary?->status);
        $this->assertNull($summary?->summary_tldr);
        $this->assertNull($summary?->model);
        $this->assertNull($summary?->prompt_version);
        $this->assertNull($summary?->tokens_in);
        $this->assertNull($summary?->tokens_out);
    }

    public function test_content_update_with_hash_change_can_auto_dispatch_summary_job(): void
    {
        config()->set('ai.summary.auto_dispatch', true);
        Queue::fake();

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'queued-on-update',
            'title' => 'Queued On Update',
            'body' => 'Version one',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        Queue::assertPushed(GenerateContentSummaryJob::class);
        Queue::fake();

        $content->update([
            'body' => 'Version two',
        ]);

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id && $job->version > 1;
        });
    }

    public function test_non_hash_field_update_does_not_reset_summary(): void
    {
        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'news',
            'title' => 'News',
            'body' => 'Unchanged body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $content->summary()->update([
            'summary_tldr' => 'Ready summary with enough context',
            'summary_bullets' => ['Bullet one', 'Bullet two'],
            'summary_meta_description' => 'Short valid meta description',
            'summary_faq' => [['question' => 'Q', 'answer' => 'A']],
            'summary_tags' => ['tag-1', 'tag-2'],
            'status' => SummaryStatus::READY,
        ]);

        $content->update([
            'status' => ContentStatus::PUBLISHED,
        ]);

        $summary = $content->summary()->first();

        $this->assertSame(SummaryStatus::READY, $summary?->status);
        $this->assertSame('Ready summary with enough context', $summary?->summary_tldr);
    }

    public function test_publish_is_blocked_when_quality_gate_fails(): void
    {
        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'blocked-publish',
            'title' => 'Blocked Publish',
            'body' => 'Needs quality gate',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $content->summary()->update([
            'summary_tldr' => 'Too short',
            'summary_bullets' => ['Only one bullet'],
            'summary_meta_description' => null,
            'summary_faq' => [],
            'summary_tags' => ['one'],
            'status' => SummaryStatus::READY,
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Regenerate summary');

        try {
            $content->update([
                'status' => ContentStatus::PUBLISHED,
            ]);
        } finally {
            $content->refresh();
            $this->assertSame(ContentStatus::DRAFT, $content->status);
        }
    }
}
