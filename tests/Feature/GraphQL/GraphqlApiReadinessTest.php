<?php

namespace Tests\Feature\GraphQL;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GraphqlApiReadinessTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_content_query_hides_draft_content(): void
    {
        $draft = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'draft-hidden',
            'title' => 'Draft Hidden',
            'body' => 'Draft body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<GRAPHQL
query GetContent(\$id: ID!) {
  content(id: \$id) {
    id
    slug
    status
  }
}
GRAPHQL,
            'variables' => ['id' => $draft->id],
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.content', null);
    }

    public function test_authenticated_content_query_can_access_draft_content(): void
    {
        $this->actingAs(User::factory()->create());

        $draft = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'draft-visible',
            'title' => 'Draft Visible',
            'body' => 'Draft body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<GRAPHQL
query GetContent(\$id: ID!) {
  content(id: \$id) {
    id
    slug
    status
  }
}
GRAPHQL,
            'variables' => ['id' => $draft->id],
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.content.slug', 'draft-visible');
        $response->assertJsonPath('data.content.status', 'DRAFT');
    }

    public function test_create_content_mutation_requires_authentication(): void
    {
        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
mutation {
  createContent(
    type: POST
    slug: "auth-required"
    title: "Auth Required"
    body: "Body"
    locale: "en"
    status: DRAFT
  ) {
    id
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.createContent', null);
        $response->assertJsonPath('errors.0.message', 'Unauthenticated.');
    }

    public function test_create_content_mutation_validates_duplicate_slug_per_locale(): void
    {
        $this->actingAs(User::factory()->create());

        Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'duplicate-slug',
            'title' => 'Existing',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
mutation {
  createContent(
    type: POST
    slug: "duplicate-slug"
    title: "Another Title"
    body: "Body"
    locale: "en"
    status: DRAFT
  ) {
    id
  }
}
GRAPHQL,
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.createContent', null);
        $response->assertJsonPath('errors.0.extensions.validation.slug.0', 'The slug has already been taken.');
    }

    public function test_graphql_route_is_rate_limited(): void
    {
        config()->set('api.graphql.route_per_minute', 1);

        Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'published-rate-limit',
            'title' => 'Published',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::PUBLISHED,
        ]);

        $request = [
            'query' => <<<'GRAPHQL'
query {
  contents(first: 1) {
    data {
      id
    }
  }
}
GRAPHQL,
        ];

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77'])
            ->postJson('/graphql', $request)
            ->assertOk();

        $this->withServerVariables(['REMOTE_ADDR' => '127.0.0.77'])
            ->postJson('/graphql', $request)
            ->assertStatus(429);
    }
}
