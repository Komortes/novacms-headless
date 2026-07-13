<?php

namespace Tests\Feature\Postgres;

use App\AI\AiProviderFactory;
use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ContentEmbeddingGenerator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Mockery;
use Tests\TestCase;

class PgvectorIntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_pgvector_storage_and_semantic_search_use_the_configured_dimensions(): void
    {
        if (DB::getDriverName() !== 'pgsql') {
            $this->markTestSkipped('This integration test requires PostgreSQL with pgvector.');
        }

        config()->set('ai.embeddings.dimensions', 3);
        config()->set('ai.embeddings.provider', 'ollama');
        config()->set('ai.embeddings.model', 'nomic-embed-text');

        $provider = new class implements AiProviderInterface
        {
            public function generate(string $prompt, array $options = []): AiGenerationResult
            {
                return new AiGenerationResult(text: '{}', model: 'fake-model');
            }

            public function embed(string $input, array $options = []): array
            {
                return [1.0, 0.0, 0.0];
            }
        };

        $factory = Mockery::mock(AiProviderFactory::class);
        $factory->shouldReceive('make')->andReturn($provider);
        $this->app->instance(AiProviderFactory::class, $factory);

        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'pgvector-match',
            'title' => 'Pgvector Match',
            'body' => 'Semantic search integration body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $result = app(ContentEmbeddingGenerator::class)->generateForContent($content);

        $this->assertFalse($result['stale']);
        $this->assertSame(3, $result['dimensions']);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
query {
  semanticSearch(query: "integration query", limit: 1) {
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
        $response->assertJsonPath('data.semanticSearch.0.content.slug', 'pgvector-match');
    }
}
