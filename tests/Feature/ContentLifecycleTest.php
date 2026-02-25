<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'summary_tldr' => 'Ready summary',
            'status' => SummaryStatus::READY,
        ]);

        $content->update([
            'status' => ContentStatus::PUBLISHED,
        ]);

        $summary = $content->summary()->first();

        $this->assertSame(SummaryStatus::READY, $summary?->status);
        $this->assertSame('Ready summary', $summary?->summary_tldr);
    }
}
