<?php

namespace Tests\Feature\Console;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Jobs\GenerateContentEmbeddingsJob;
use App\Models\Content;
use App\Models\ContentEmbedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class EmbeddingCommandsTest extends TestCase
{
    use RefreshDatabase;

    public function test_reindex_embeddings_command_queues_jobs_by_default(): void
    {
        config()->set('ai.embeddings.auto_dispatch', false);
        Queue::fake();

        $stale = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embed-queued',
            'title' => 'Embed Queued',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $fresh = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embed-fresh',
            'title' => 'Embed Fresh',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $fresh->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $fresh->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $this->artisan('content:reindex-embeddings')
            ->expectsOutput('Embedding reindex queued.')
            ->expectsOutput('Mode: incremental')
            ->expectsOutput('Queued items: 1')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateContentEmbeddingsJob::class, function (GenerateContentEmbeddingsJob $job) use ($stale): bool {
            return $job->contentId === $stale->id;
        });
        Queue::assertPushed(GenerateContentEmbeddingsJob::class, 1);
    }

    public function test_reindex_embeddings_command_can_run_sync_for_single_content(): void
    {
        config()->set('ai.embeddings.chunk_chars', 5000);
        $this->mockEmbeddingProvider([0.2, 0.4, 0.6]);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embed-sync',
            'title' => 'Embed Sync',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->artisan("content:reindex-embeddings {$content->slug} --sync")
            ->expectsOutputToContain('Embedding reindex completed')
            ->assertExitCode(0);

        $this->assertDatabaseHas('content_embeddings', [
            'content_id' => $content->id,
            'chunk_index' => 0,
            'dimensions' => 3,
            'model' => 'nomic-embed-text',
        ]);
    }

    public function test_reindex_embeddings_command_can_force_full_reindex(): void
    {
        config()->set('ai.embeddings.auto_dispatch', false);
        Queue::fake();

        $first = Content::create([
            'type' => ContentType::POST,
            'slug' => 'full-first',
            'title' => 'Full First',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $second = Content::create([
            'type' => ContentType::POST,
            'slug' => 'full-second',
            'title' => 'Full Second',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $first->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $first->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.1, 0.2, 0.3],
        ]);

        $this->artisan('content:reindex-embeddings --mode=full')
            ->expectsOutput('Embedding reindex queued.')
            ->expectsOutput('Mode: full')
            ->expectsOutput('Queued items: 2')
            ->assertExitCode(0);

        Queue::assertPushed(GenerateContentEmbeddingsJob::class, 2);
        Queue::assertPushed(GenerateContentEmbeddingsJob::class, fn (GenerateContentEmbeddingsJob $job): bool => in_array($job->contentId, [$first->id, $second->id], true));
    }

    public function test_reindex_embeddings_command_rejects_invalid_mode(): void
    {
        $this->artisan('content:reindex-embeddings --mode=invalid')
            ->expectsOutput('Invalid --mode. Allowed: incremental, full')
            ->assertExitCode(1);
    }

    /**
     * @param  list<float>  $vector
     */
    private function mockEmbeddingProvider(array $vector): void
    {
        $provider = new class($vector) implements AiProviderInterface
        {
            /**
             * @param  list<float>  $vector
             */
            public function __construct(
                private readonly array $vector,
            ) {}

            public function generate(string $prompt, array $options = []): AiGenerationResult
            {
                return new AiGenerationResult(
                    text: '{}',
                    model: 'fake-model',
                );
            }

            public function embed(string $input, array $options = []): array
            {
                return $this->vector;
            }
        };

        $factory = Mockery::mock(AiProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);

        $this->app->instance(AiProviderFactory::class, $factory);
    }
}
