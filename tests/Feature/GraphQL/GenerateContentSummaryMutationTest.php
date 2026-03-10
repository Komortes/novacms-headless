<?php

namespace Tests\Feature\GraphQL;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Jobs\GenerateContentSummaryJob;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class GenerateContentSummaryMutationTest extends TestCase
{
    use RefreshDatabase;

    public function test_generate_content_summary_mutation_queues_job_and_returns_pending_summary(): void
    {
        Queue::fake();
        $this->actingAs(User::factory()->create());

        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'graphql-generate-summary',
            'title' => 'GraphQL Generate Summary',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $response = $this->postJson('/graphql', [
            'query' => <<<'GRAPHQL'
mutation GenerateSummary($contentId: ID!) {
  generateContentSummary(content_id: $contentId, prompt_version: "1.0.0") {
    id
    content_id
    status
  }
}
GRAPHQL,
            'variables' => [
                'contentId' => $content->id,
            ],
        ]);

        $response->assertOk();
        $response->assertJsonMissingPath('errors');
        $response->assertJsonPath('data.generateContentSummary.content_id', (string) $content->id);
        $response->assertJsonPath('data.generateContentSummary.status', 'PENDING');

        Queue::assertPushed(GenerateContentSummaryJob::class, function (GenerateContentSummaryJob $job) use ($content): bool {
            return $job->contentId === $content->id
                && $job->promptVersion === '1.0.0'
                && $job->version > 0;
        });

        $this->assertDatabaseHas('content_ai_summaries', [
            'content_id' => $content->id,
            'status' => SummaryStatus::PENDING->value,
        ]);
    }
}
