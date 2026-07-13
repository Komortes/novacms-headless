<?php

namespace Tests\Feature;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\AI\Exceptions\AiProviderException;
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
        config()->set('ai.embeddings.dimensions', 3);

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

    public function test_it_preserves_existing_embeddings_when_a_later_chunk_fails(): void
    {
        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
        config()->set('ai.embeddings.chunk_chars', 250);
        config()->set('ai.embeddings.dimensions', 3);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'atomic-embeddings',
            'title' => 'Atomic Embeddings',
            'body' => str_repeat('A', 300)."\n\n".str_repeat('B', 300),
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $content->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $content->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.9, 0.8, 0.7],
        ]);

        $provider = new class implements AiProviderInterface
        {
            private int $calls = 0;

            public function generate(string $prompt, array $options = []): AiGenerationResult
            {
                return new AiGenerationResult(text: '{}', model: 'fake-model');
            }

            public function embed(string $input, array $options = []): array
            {
                $this->calls++;

                if ($this->calls > 1) {
                    throw new AiProviderException('Second chunk failed.');
                }

                return [0.1, 0.2, 0.3];
            }
        };

        $factory = Mockery::mock(AiProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);
        $this->app->instance(AiProviderFactory::class, $factory);

        try {
            app(ContentEmbeddingGenerator::class)->generateForContent($content);
            $this->fail('Expected the second embedding chunk to fail.');
        } catch (AiProviderException $exception) {
            $this->assertSame('Second chunk failed.', $exception->getMessage());
        }

        $embedding = ContentEmbedding::query()->where('content_id', $content->id)->sole();
        $this->assertSame([0.9, 0.8, 0.7], $embedding->embedding);
    }

    public function test_it_rejects_vectors_that_do_not_match_the_configured_storage_dimensions(): void
    {
        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
        config()->set('ai.embeddings.dimensions', 4);
        $this->mockEmbeddingProvider([0.1, 0.2, 0.3]);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'wrong-embedding-dimensions',
            'title' => 'Wrong Embedding Dimensions',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('returned 3 dimensions; configured storage expects 4');

        try {
            app(ContentEmbeddingGenerator::class)->generateForContent($content);
        } finally {
            $this->assertDatabaseMissing('content_embeddings', [
                'content_id' => $content->id,
            ]);
        }
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
