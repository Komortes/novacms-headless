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
            'status' => ContentStatus::PUBLISHED,
        ]);

        $second = Content::create([
            'type' => ContentType::POST,
            'slug' => 'second-match',
            'title' => 'Second Match',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $otherLocale = Content::create([
            'type' => ContentType::POST,
            'slug' => 'other-locale',
            'title' => 'Other Locale',
            'body' => 'Body',
            'locale' => 'uk',
            'status' => ContentStatus::PUBLISHED,
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

    public function test_public_semantic_search_only_returns_published_content(): void
    {
        $this->mockEmbeddingProvider([1.0, 0.0, 0.0]);

        $published = Content::create([
            'type' => ContentType::POST,
            'slug' => 'published-match',
            'title' => 'Published Match',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $draft = Content::create([
            'type' => ContentType::POST,
            'slug' => 'draft-match',
            'title' => 'Draft Match',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        ContentEmbedding::create([
            'content_id' => $published->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $published->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        ContentEmbedding::create([
            'content_id' => $draft->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => $draft->content_hash,
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(query: "search text", limit: 5, status: DRAFT) {
    content {
      slug
    }
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonCount(1, 'data.semanticSearch');
        $response->assertJsonPath('data.semanticSearch.0.content.slug', 'published-match');
    }

    public function test_semantic_search_ignores_embeddings_for_an_outdated_content_hash(): void
    {
        $this->mockEmbeddingProvider([1.0, 0.0, 0.0]);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'stale-embedding',
            'title' => 'Stale Embedding',
            'body' => 'Current body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        ContentEmbedding::create([
            'content_id' => $content->id,
            'source' => 'body',
            'chunk_index' => 0,
            'content_hash' => str_repeat('0', 64),
            'provider' => 'ollama',
            'model' => 'nomic-embed-text',
            'dimensions' => 3,
            'embedding' => [1.0, 0.0, 0.0],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(query: "search text") {
    content {
      slug
    }
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonCount(0, 'data.semanticSearch');
    }

    public function test_semantic_search_can_filter_by_status_type_and_min_score(): void
    {
        $this->mockEmbeddingProvider([1.0, 0.0, 0.0]);

        $publishedPost = Content::create([
            'type' => ContentType::POST,
            'slug' => 'published-post',
            'title' => 'Published Post',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $draftPost = Content::create([
            'type' => ContentType::POST,
            'slug' => 'draft-post',
            'title' => 'Draft Post',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $publishedPage = Content::create([
            'type' => ContentType::PAGE,
            'slug' => 'published-page',
            'title' => 'Published Page',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        ContentEmbedding::insert([
            [
                'content_id' => $publishedPost->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $publishedPost->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([0.98, 0.02, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content_id' => $draftPost->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $draftPost->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([1.0, 0.0, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content_id' => $publishedPage->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $publishedPage->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([0.99, 0.01, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(
    query: "search text",
    limit: 5,
    locale: "en",
    status: PUBLISHED,
    type: POST,
    min_score: 0.95
  ) {
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
        $response->assertJsonCount(1, 'data.semanticSearch');
        $response->assertJsonPath('data.semanticSearch.0.content.slug', 'published-post');
    }

    public function test_related_content_excludes_source_record(): void
    {
        $base = Content::create([
            'type' => ContentType::POST,
            'slug' => 'base-content',
            'title' => 'Base Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $related = Content::create([
            'type' => ContentType::POST,
            'slug' => 'related-content',
            'title' => 'Related Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $far = Content::create([
            'type' => ContentType::POST,
            'slug' => 'far-content',
            'title' => 'Far Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
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

    public function test_related_content_can_apply_filters_and_threshold(): void
    {
        $base = Content::create([
            'type' => ContentType::POST,
            'slug' => 'base-filtered',
            'title' => 'Base Filtered',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $matching = Content::create([
            'type' => ContentType::POST,
            'slug' => 'matching-related',
            'title' => 'Matching Related',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $draft = Content::create([
            'type' => ContentType::POST,
            'slug' => 'draft-related',
            'title' => 'Draft Related',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $page = Content::create([
            'type' => ContentType::PAGE,
            'slug' => 'page-related',
            'title' => 'Page Related',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        ContentEmbedding::insert([
            [
                'content_id' => $base->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $base->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([1.0, 0.0, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content_id' => $matching->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $matching->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([0.93, 0.07, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content_id' => $draft->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $draft->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([0.96, 0.04, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'content_id' => $page->id,
                'source' => 'body',
                'chunk_index' => 0,
                'content_hash' => $page->content_hash,
                'provider' => 'ollama',
                'model' => 'nomic-embed-text',
                'dimensions' => 3,
                'embedding' => json_encode([0.97, 0.03, 0.0]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<GRAPHQL
query {
  relatedContent(
    content_id: {$base->id},
    limit: 5,
    locale: "en",
    status: PUBLISHED,
    type: POST,
    min_score: 0.9
  ) {
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
        $response->assertJsonCount(1, 'data.relatedContent');
        $response->assertJsonPath('data.relatedContent.0.content.slug', 'matching-related');
    }

    public function test_semantic_search_rejects_invalid_limit(): void
    {
        $this->mockEmbeddingProvider([1.0, 0.0, 0.0]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(query: "search text", limit: 999) {
    score
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.semanticSearch', null);
        $response->assertJsonPath('errors.0.extensions.validation.limit.0', 'The limit field must not be greater than 20.');
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
