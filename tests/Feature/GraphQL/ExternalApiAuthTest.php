<?php

namespace Tests\Feature\GraphQL;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExternalApiAuthTest extends TestCase
{
    use RefreshDatabase;

    public function test_api_token_with_write_ability_can_create_content(): void
    {
        $user = User::factory()->create();
        $issued = $user->issueApiToken('graphql-writer', ['graphql:write']);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$issued['plain_text_token'])
            ->postJson('/graphql', [
                'query' => <<<'GRAPHQL'
mutation {
  createContent(
    type: POST
    slug: "token-created-post"
    title: "Token Created Post"
    body: "Body"
    locale: "en"
    status: DRAFT
  ) {
    id
    slug
    status
  }
}
GRAPHQL,
            ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.createContent.slug', 'token-created-post');
        $response->assertJsonPath('data.createContent.status', 'DRAFT');
    }

    public function test_api_token_without_write_ability_cannot_create_content(): void
    {
        $user = User::factory()->create();
        $issued = $user->issueApiToken('graphql-readonly', ['graphql:read-internal']);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$issued['plain_text_token'])
            ->postJson('/graphql', [
                'query' => <<<'GRAPHQL'
mutation {
  createContent(
    type: POST
    slug: "token-denied-post"
    title: "Token Denied Post"
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
        $this->assertStringContainsString('graphql:write', (string) $response->json('errors.0.message'));
    }

    public function test_api_token_with_internal_read_ability_can_read_draft_content(): void
    {
        $draft = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'draft-via-token',
            'title' => 'Draft Via Token',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $user = User::factory()->create();
        $issued = $user->issueApiToken('graphql-reader', ['graphql:read-internal']);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$issued['plain_text_token'])
            ->postJson('/graphql', [
                'query' => <<<GRAPHQL
query {
  content(id: {$draft->id}) {
    id
    slug
    status
  }
}
GRAPHQL,
            ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.content.slug', 'draft-via-token');
        $response->assertJsonPath('data.content.status', 'DRAFT');
    }

    public function test_api_token_without_internal_read_ability_only_sees_published_content(): void
    {
        $draft = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'hidden-draft-via-token',
            'title' => 'Hidden Draft Via Token',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $user = User::factory()->create();
        $issued = $user->issueApiToken('graphql-write-only', ['graphql:write']);

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$issued['plain_text_token'])
            ->postJson('/graphql', [
                'query' => <<<GRAPHQL
query {
  content(id: {$draft->id}) {
    id
    slug
  }
}
GRAPHQL,
            ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.content', null);
    }

    public function test_revoked_api_token_is_rejected(): void
    {
        $user = User::factory()->create();
        $issued = $user->issueApiToken('graphql-revoked', ['graphql:write']);
        $issued['access_token']->revoke();

        $response = $this
            ->withHeader('Authorization', 'Bearer '.$issued['plain_text_token'])
            ->postJson('/graphql', [
                'query' => <<<'GRAPHQL'
mutation {
  createContent(
    type: POST
    slug: "revoked-token-post"
    title: "Revoked Token Post"
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
}
