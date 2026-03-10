<?php

namespace Tests\Feature;

use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentEmbedding;
use App\Services\ContentEmbeddingDispatcher;
use App\Services\ContentSummaryDispatcher;
use App\Services\QueueWorkerRunner;
use App\Services\RuntimeE2eSmokeService;
use App\Services\RuntimeHealthService;
use App\Services\SemanticSearchService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class RuntimeE2eSmokeServiceTest extends TestCase
{
    use RefreshDatabase;

    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_it_runs_successful_smoke_flow_and_cleans_up_records(): void
    {
        $health = Mockery::mock(RuntimeHealthService::class);
        $health->shouldReceive('collect')->once()->andReturn([
            'ok' => true,
            'checks' => [
                ['component' => 'Database', 'status' => 'ok', 'message' => 'ok', 'meta' => []],
                ['component' => 'Redis', 'status' => 'ok', 'message' => 'ok', 'meta' => []],
                ['component' => 'Ollama', 'status' => 'ok', 'message' => 'ok', 'meta' => []],
            ],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $health);

        $summaryDispatcher = Mockery::mock(ContentSummaryDispatcher::class);
        $summaryDispatcher->shouldReceive('dispatch')->once();
        $this->app->instance(ContentSummaryDispatcher::class, $summaryDispatcher);

        $embeddingDispatcher = Mockery::mock(ContentEmbeddingDispatcher::class);
        $embeddingDispatcher->shouldReceive('dispatch')->once();
        $this->app->instance(ContentEmbeddingDispatcher::class, $embeddingDispatcher);

        $queueRunner = Mockery::mock(QueueWorkerRunner::class);
        $queueRunner->shouldReceive('runOnce')->once()->andReturnUsing(function (): array {
            $content = Content::query()->latest('id')->firstOrFail();

            ContentAiSummary::query()->updateOrCreate(
                ['content_id' => $content->id],
                [
                    'status' => SummaryStatus::READY,
                    'summary_tldr' => 'Smoke summary',
                    'model' => 'fake-summary-model',
                    'prompt_version' => '1.0.0',
                ],
            );

            return ['exit_code' => 0, 'output' => 'Processed summary job'];
        });
        $queueRunner->shouldReceive('runOnce')->once()->andReturnUsing(function (): array {
            $content = Content::query()->latest('id')->firstOrFail();

            ContentEmbedding::query()->create([
                'content_id' => $content->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $content->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => [0.1, 0.2, 0.3],
            ]);

            return ['exit_code' => 0, 'output' => 'Processed embedding job'];
        });
        $this->app->instance(QueueWorkerRunner::class, $queueRunner);

        $search = Mockery::mock(SemanticSearchService::class);
        $search->shouldReceive('semanticSearch')->once()->andReturnUsing(function (): array {
            $content = Content::query()->latest('id')->firstOrFail();

            return [[
                'content' => $content,
                'score' => 0.98,
            ]];
        });
        $this->app->instance(SemanticSearchService::class, $search);

        $report = $this->app->make(RuntimeE2eSmokeService::class)->run([
            'timeout_seconds' => 5,
        ]);

        $this->assertTrue($report['ok']);
        $this->assertSame(['health', 'prompts', 'content', 'summary', 'embeddings', 'search', 'cleanup'], collect($report['steps'])->pluck('name')->all());
        $this->assertDatabaseCount('contents', 0);
        $this->assertDatabaseCount('content_ai_summaries', 0);
        $this->assertDatabaseCount('content_embeddings', 0);
    }

    public function test_it_returns_failed_report_when_required_health_check_is_not_ok(): void
    {
        $health = Mockery::mock(RuntimeHealthService::class);
        $health->shouldReceive('collect')->once()->andReturn([
            'ok' => false,
            'checks' => [
                ['component' => 'Database', 'status' => 'failed', 'message' => 'no db', 'meta' => []],
            ],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $health);

        $summaryDispatcher = Mockery::mock(ContentSummaryDispatcher::class);
        $summaryDispatcher->shouldNotReceive('dispatch');
        $this->app->instance(ContentSummaryDispatcher::class, $summaryDispatcher);

        $embeddingDispatcher = Mockery::mock(ContentEmbeddingDispatcher::class);
        $embeddingDispatcher->shouldNotReceive('dispatch');
        $this->app->instance(ContentEmbeddingDispatcher::class, $embeddingDispatcher);

        $queueRunner = Mockery::mock(QueueWorkerRunner::class);
        $queueRunner->shouldNotReceive('runOnce');
        $this->app->instance(QueueWorkerRunner::class, $queueRunner);

        $search = Mockery::mock(SemanticSearchService::class);
        $search->shouldNotReceive('semanticSearch');
        $this->app->instance(SemanticSearchService::class, $search);

        $report = $this->app->make(RuntimeE2eSmokeService::class)->run();

        $this->assertFalse($report['ok']);
        $this->assertSame('failure', $report['steps'][0]['name']);
        $this->assertStringContainsString('Database', $report['steps'][0]['message']);
        $this->assertDatabaseCount('contents', 0);
    }
}
