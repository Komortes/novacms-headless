<?php

namespace Tests\Feature\GraphQL;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\ContentEmbedding;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class SemanticSearchQueryTest extends TestCase
{
    use RefreshDatabase;

    public function test_semantic_search_returns_ranked_results(): void
    {
        $this->mockEmbeddingProvider([1.0, 0.0, 0.0]);

        $top = Content::create([
            'type' => ContentType::POST,
            'slug' => 'top-match',
            'title' => 'Top Match',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $second = Content::create([
            'type' => ContentType::POST,
            'slug' => 'second-match',
            'title' => 'Second Match',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $otherLocale = Content::create([
            'type' => ContentType::POST,
            'slug' => 'other-locale',
            'title' => 'Other Locale',
            'body' => 'Body',
            'locale' => 'uk',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $top->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $top->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        ContentEmbedding::create([
            'content_id' => $second->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $second->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.7, 0.3, 0.0],
        ]);

        ContentEmbedding::create([
            'content_id' => $otherLocale->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $otherLocale->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(query: "search text", limit: 2, locale: "en") {
    score
    content {
      slug
    }
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.semanticSearch.0.content.slug', 'top-match');
        $response->assertJsonPath('data.semanticSearch.1.content.slug', 'second-match');
    }

    public function test_related_content_excludes_source_record(): void
    {
        $base = Content::create([
            'type' => ContentType::POST,
            'slug' => 'base-content',
            'title' => 'Base Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $related = Content::create([
            'type' => ContentType::POST,
            'slug' => 'related-content',
            'title' => 'Related Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $far = Content::create([
            'type' => ContentType::POST,
            'slug' => 'far-content',
            'title' => 'Far Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $base->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $base->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        ContentEmbedding::create([
            'content_id' => $related->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $related->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.9, 0.1, 0.0],
        ]);

        ContentEmbedding::create([
            'content_id' => $far->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $far->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [0.0, 1.0, 0.0],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<GRAPHQL
query {
  relatedContent(content_id: {$base->id}, limit: 2) {
    score
    content {
      slug
    }
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.relatedContent.0.content.slug', 'related-content');

        $slugs = collect($response->json('data.relatedContent'))->pluck('content.slug')->all();
        $this->assertNotContains('base-content', $slugs);
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
                    model: 'fake',
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
