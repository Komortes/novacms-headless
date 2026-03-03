<?php

namespace Tests\Feature;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\ContentEmbedding;
use App\Models\ContentEmbeddingEvent;
use App\Services\ContentEmbeddingDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ContentEmbeddingLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_dispatch_runs_embedding_pipeline_and_writes_events(): void
    {
        $this->mockEmbeddingProvider([0.4, 0.5, 0.6]);
        config()->set('domain_events.stream.enabled', false);
        config()->set('domain_events.broadcast.enabled', false);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embedding-lifecycle',
            'title' => 'Embedding Lifecycle',
            'body' => "Paragraph one.\n\nParagraph two.",
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        app(ContentEmbeddingDispatcher::class)->dispatch($content, 'ollama', 'nomic-embed-text');

        $events = ContentEmbeddingEvent::query()
            ->where('content_id', $content->id)
            ->orderBy('id')
            ->pluck('event')
            ->all();

        $this->assertContains('queued', $events);
        $this->assertContains('started', $events);
        $this->assertContains('completed', $events);

        $this->assertDatabaseHas('content_embeddings', [
            'content_id' => $content->id,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
        ]);

        $this->assertGreaterThan(0, ContentEmbedding::query()->where('content_id', $content->id)->count());
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
            ) {
            }

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
