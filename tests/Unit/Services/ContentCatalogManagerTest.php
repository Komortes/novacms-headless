<?php

namespace Tests\Unit\Services;

use App\DomainEvents;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentEmbeddingsJob;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use App\Services\ContentCatalogManager;
use App\Services\DomainEventPublisher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Validation\ValidationException;
use Mockery;
use Tests\TestCase;

class ContentCatalogManagerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
    }

    public function test_it_exports_content_in_bundle_format(): void
    {
        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'bundle-export',
            'title' => 'Bundle Export',
            'body' => 'Export me',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $content->summary()->updateOrCreate(
            ['content_id' => $content->id],
            [
                'status' => SummaryStatus::READY,
                'summary_tldr' => 'Ready export summary with enough detail.',
                'summary_bullets' => ['One', 'Two'],
                'summary_meta_description' => 'Bundle export meta description.',
                'summary_faq' => [['question' => 'Q?', 'answer' => 'A.']],
                'summary_tags' => ['export', 'bundle'],
                'model' => 'qwen2.5:1.5b',
                'prompt_version' => '1.0.0',
            ],
        );

        $bundle = app(ContentCatalogManager::class)->export();

        $this->assertArrayHasKey('exported_at', $bundle);
        $this->assertSame(1, $bundle['count']);
        $this->assertCount(1, $bundle['contents']);
        $this->assertSame('bundle-export', $bundle['contents'][0]['slug']);
        $this->assertSame('ready', $bundle['contents'][0]['summary']['status']);
    }

    public function test_it_imports_content_bundle_and_upserts_summary(): void
    {
        $payload = json_encode([
            'contents' => [
                [
                    'type' => 'post',
                    'slug' => 'bundle-import',
                    'title' => 'Bundle Import',
                    'body' => 'Imported body',
                    'locale' => 'en',
                    'status' => 'published',
                    'summary' => [
                        'status' => 'ready',
                        'summary_tldr' => 'Imported summary with enough length for readiness.',
                        'summary_bullets' => ['One', 'Two'],
                        'summary_meta_description' => 'Imported bundle meta description.',
                        'summary_faq' => [['question' => 'What?', 'answer' => 'This.']],
                        'summary_tags' => ['import', 'bundle'],
                        'model' => 'qwen2.5:1.5b',
                        'prompt_version' => '1.0.0',
                        'tokens_in' => 120,
                        'tokens_out' => 60,
                        'generation_ms' => 900,
                    ],
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $result = app(ContentCatalogManager::class)->importFromJson((string) $payload, true);

        $this->assertSame(1, $result['imported']);
        $this->assertSame(1, $result['created']);
        $this->assertSame(0, $result['updated']);
        $this->assertSame(1, $result['summaries']);

        $this->assertDatabaseHas('contents', [
            'slug' => 'bundle-import',
            'locale' => 'en',
            'status' => 'published',
        ]);
        $this->assertDatabaseHas('content_ai_summaries', [
            'status' => 'ready',
            'model' => 'qwen2.5:1.5b',
            'prompt_version' => '1.0.0',
        ]);
    }

    public function test_it_invalidates_existing_summary_when_import_changes_content_without_summary(): void
    {
        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'catalog-update',
            'title' => 'Catalog Update',
            'body' => 'Original body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $content->summary()->update([
            'status' => SummaryStatus::READY,
            'summary_tldr' => 'An old summary that must not survive a content update.',
            'summary_bullets' => ['One', 'Two'],
            'summary_meta_description' => 'Old metadata.',
            'summary_faq' => [['question' => 'Old?', 'answer' => 'Yes.']],
            'summary_tags' => ['old', 'summary'],
        ]);

        $payload = json_encode([
            'contents' => [[
                'type' => 'post',
                'slug' => 'catalog-update',
                'title' => 'Catalog Update',
                'body' => 'Updated body',
                'locale' => 'en',
                'status' => 'draft',
            ]],
        ]);

        app(ContentCatalogManager::class)->importFromJson((string) $payload);

        $summary = $content->fresh()->summary;
        $this->assertSame(SummaryStatus::PENDING, $summary?->status);
        $this->assertNull($summary?->summary_tldr);
        $this->assertNull($summary?->model);
    }

    public function test_it_rejects_published_import_without_ready_summary(): void
    {
        $payload = json_encode([
            'contents' => [[
                'type' => 'post',
                'slug' => 'invalid-published-import',
                'title' => 'Invalid Published Import',
                'body' => 'Imported body',
                'locale' => 'en',
                'status' => 'published',
            ]],
        ]);

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('AI summary must be in "ready" state');

        app(ContentCatalogManager::class)->importFromJson((string) $payload);
    }

    public function test_it_dispatches_post_commit_work_for_imported_content_without_summary(): void
    {
        config()->set('ai.summary.auto_dispatch', true);
        config()->set('ai.embeddings.auto_dispatch', true);
        Queue::fake();

        $publisher = Mockery::mock(DomainEventPublisher::class);
        $publisher->shouldReceive('publish')
            ->once()
            ->withArgs(fn (string $event, array $payload): bool => $event === DomainEvents::CONTENT_UPDATED
                && ($payload['slug'] ?? null) === 'post-commit-import');
        $this->app->instance(DomainEventPublisher::class, $publisher);

        $payload = json_encode([
            'contents' => [[
                'type' => 'post',
                'slug' => 'post-commit-import',
                'title' => 'Post Commit Import',
                'body' => 'Imported body',
                'locale' => 'en',
                'status' => 'draft',
            ]],
        ]);

        app(ContentCatalogManager::class)->importFromJson((string) $payload);

        Queue::assertPushed(GenerateContentSummaryJob::class, 1);
        Queue::assertPushed(GenerateContentEmbeddingsJob::class, 1);
    }
}
