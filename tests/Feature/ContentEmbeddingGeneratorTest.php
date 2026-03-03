<?php

namespace Tests\Feature;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\ContentEmbedding;
use App\Services\ContentEmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class ContentEmbeddingGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_embeddings_and_cleans_outdated_chunks(): void
    {
        config()->set('ai.embeddings.chunk_chars', 60);
        config()->set('ai.embeddings.max_chunks', 10);

        $this->mockEmbeddingProvider([0.11, 0.22, 0.33]);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embed-content',
            'title' => 'Embed Content',
            'body' => str_repeat("Paragraph for embedding chunking.\n\n", 12),
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $first = app(ContentEmbeddingGenerator::class)->generateForContent(
            $content,
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $this->assertGreaterThanOrEqual(2, $first['chunks']);
        $this->assertSame($first['chunks'], ContentEmbedding::query()->where('content_id', $content->id)->count());

        $content->update([
            'body' => 'Short body now.',
        ]);

        $second = app(ContentEmbeddingGenerator::class)->generateForContent(
            $content->fresh(),
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        $this->assertSame(1, $second['chunks']);
        $this->assertGreaterThanOrEqual(1, $second['deleted']);
        $this->assertSame(1, ContentEmbedding::query()->where('content_id', $content->id)->count());
        $this->assertDatabaseHas('content_embeddings', [
            'content_id' => $content->id,
            'chunk_index' => 0,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'content_hash' => $content->fresh()->content_hash,
        ]);
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
