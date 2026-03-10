<?php

namespace Tests\Feature\Console;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Tests\TestCase;

class ContentCatalogCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
    }

    public function test_content_export_command_writes_bundle_to_path(): void
    {
        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'command-export',
            'title' => 'Command Export',
            'body' => 'Export command body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $content->summary()->updateOrCreate(
            ['content_id' => $content->id],
            [
                'status' => SummaryStatus::READY,
                'summary_tldr' => 'Command export summary with valid length.',
                'summary_bullets' => ['One', 'Two'],
                'summary_meta_description' => 'Command export meta description.',
                'summary_faq' => [['question' => 'Q?', 'answer' => 'A.']],
                'summary_tags' => ['command', 'export'],
            ],
        );

        $path = storage_path('app/testing/content-export.json');
        File::delete($path);

        $this->artisan("content:export --path={$path}")
            ->expectsOutputToContain('Content bundle exported.')
            ->expectsOutputToContain('Items: 1')
            ->assertSuccessful();

        $this->assertTrue(File::exists($path));

        $decoded = json_decode((string) File::get($path), true);

        $this->assertIsArray($decoded);
        $this->assertSame('command-export', $decoded['contents'][0]['slug']);
    }

    public function test_content_import_command_can_load_demo_dataset(): void
    {
        $this->artisan('content:import --demo')
            ->expectsOutputToContain('Demo content imported.')
            ->expectsOutputToContain('Imported: 4')
            ->expectsOutputToContain('Created: 4')
            ->assertSuccessful();

        $this->assertDatabaseCount('contents', 4);
        $this->assertDatabaseHas('contents', [
            'slug' => 'launching-editorial-automation',
            'locale' => 'en',
        ]);
        $this->assertDatabaseHas('content_ai_summaries', [
            'status' => 'failed',
            'last_error' => 'Demo dataset: retry this item from Queue Center after the local model is available.',
        ]);
    }

    public function test_content_import_command_can_skip_existing_records_without_upsert(): void
    {
        Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'launching-editorial-automation',
            'title' => 'Existing',
            'body' => 'Existing body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->artisan('content:import --demo --no-upsert')
            ->expectsOutputToContain('Skipped: 1')
            ->assertSuccessful();

        $this->assertSame('Existing', Content::query()->where('slug', 'launching-editorial-automation')->firstOrFail()->title);
        $this->assertDatabaseCount('contents', 4);
    }
}
