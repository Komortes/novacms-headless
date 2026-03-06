<?php

namespace Tests\Feature\Console;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use Database\Seeders\PromptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_summary_command_reports_not_found_cleanly(): void
    {
        $this->artisan('content:generate-summary 1')
            ->expectsOutput('Content not found for [1].')
            ->expectsOutput('No content records exist yet.')
            ->assertExitCode(1);
    }

    public function test_create_sample_command_creates_content(): void
    {
        $this->artisan('content:create-sample --slug=from-command --title="From Command" --type=post --status=draft')
            ->expectsOutput('Sample content created.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('contents', [
            'slug' => 'from-command',
            'title' => 'From Command',
            'type' => ContentType::POST->value,
            'status' => ContentStatus::DRAFT->value,
        ]);
    }

    public function test_generate_summary_command_queues_by_default(): void
    {
        Queue::fake();

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'command-summary',
            'title' => 'Command Summary',
            'body' => 'Markdown body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->artisan('content:generate-summary command-summary')
            ->expectsOutput('Summary generation queued.')
            ->expectsOutput("Content ID: {$content->id}")
            ->assertExitCode(0);

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id && $job->version > 0;
        });

        $summary = $content->summary()->first();

        $this->assertNotNull($summary);
        $this->assertSame(SummaryStatus::PENDING, $summary?->status);
    }

    public function test_generate_summary_command_passes_provider_model_and_prompt_version_to_job(): void
    {
        Queue::fake();

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'command-summary-model',
            'title' => 'Command Summary Model',
            'body' => 'Markdown body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->artisan('content:generate-summary command-summary-model --provider=openai --model=gpt-4.1-mini --prompt-version=1.0.0')
            ->expectsOutput('Summary generation queued.')
            ->expectsOutput('Provider: openai')
            ->expectsOutput('Model: gpt-4.1-mini')
            ->expectsOutput('Prompt version: 1.0.0')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id
                && $job->provider === 'openai'
                && $job->model === 'gpt-4.1-mini'
                && $job->promptVersion === '1.0.0';
        });
    }

    public function test_generate_summary_command_supports_sync_mode_with_model_option(): void
    {
        $this->seed(PromptSeeder::class);

        app()->bind(AiProviderInterface::class, function () {
            return new class implements AiProviderInterface
            {
                public function generate(string $prompt, array $options = []): AiGenerationResult
                {
                    return new AiGenerationResult(
                        text: json_encode([
                            'summary_tldr' => 'Generated with custom model',
                            'summary_bullets' => ['A'],
                            'summary_meta_description' => 'Meta',
                            'summary_faq' => [],
                            'summary_tags' => [],
                        ]) ?: '{}',
                        model: (string) ($options['model'] ?? 'missing'),
                        tokensIn: 1,
                        tokensOut: 1,
                    );
                }

                public function embed(string $input, array $options = []): array
                {
                    return [0.1, 0.2, 0.3];
                }
            };
        });

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'command-summary-model',
            'title' => 'Command Summary Model',
            'body' => 'Markdown body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->artisan('content:generate-summary command-summary-model --sync --model=llama3.2:3b')
            ->expectsOutput('Summary generated.')
            ->expectsOutput('Model: llama3.2:3b')
            ->assertExitCode(0);

        $summary = $content->summary()->first();

        $this->assertNotNull($summary);
        $this->assertSame('llama3.2:3b', $summary?->model);
    }
}
