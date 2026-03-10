<?php

namespace Tests\Unit\Services;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\ContentCatalogManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
}
