<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Jobs\GenerateContentEmbeddingsJob;
use App\Models\Content;
use App\Services\ContentEmbeddingDispatcher;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;

class ContentEmbeddingDispatcherTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_queues_embeddings_job_with_hash_snapshot(): void
    {
        Queue::fake();

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'embedding-dispatch',
            'title' => 'Embedding Dispatch',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        app(ContentEmbeddingDispatcher::class)->dispatch(
            content: $content,
            provider: 'ollama',
            model: 'nomic-embed-text',
        );

        Queue::assertPushed(GenerateContentEmbeddingsJob::class, function (GenerateContentEmbeddingsJob $job) use ($content): bool {
            return $job->contentId === $content->id
                && $job->contentHash === $content->content_hash
                && $job->provider === 'ollama'
                && $job->model === 'nomic-embed-text';
        });
    }
}
