<?php

namespace Tests\Feature;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\AI\Exceptions\AiProviderException;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummaryEvent;
use App\Services\ContentSummaryGenerator;
use Database\Seeders\PromptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ContentSummaryGeneratorTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_generates_and_persists_summary(): void
    {
        $this->seed(PromptSeeder::class);

        app()->bind(AiProviderInterface::class, function () {
            return new class implements AiProviderInterface
            {
                public function generate(string $prompt, array $options = []): AiGenerationResult
                {
                    return new AiGenerationResult(
                        text: json_encode([
                            'summary_tldr' => 'Short TLDR',
                            'summary_bullets' => ['Point A', 'Point B', 'Point B'],
                            'summary_meta_description' => 'Meta description',
                            'summary_faq' => [
                                ['question' => 'What?', 'answer' => 'Answer'],
                                'Raw question',
                            ],
                            'summary_tags' => ['tag-a', 'tag-b'],
                        ]) ?: '{}',
                        model: 'fake-model',
                        tokensIn: 120,
                        tokensOut: 40,
                    );
                }

                public function embed(string $input, array $options = []): array
                {
                    return [0.1, 0.2, 0.3];
                }
            };
        });

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'functional-summary',
            'title' => 'Functional Summary',
            'body' => '# Heading'.PHP_EOL.PHP_EOL.'Some markdown body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $summary = app(ContentSummaryGenerator::class)->generateForContent($content);

        $this->assertSame(SummaryStatus::READY, $summary->status);
        $this->assertSame('Short TLDR', $summary->summary_tldr);
        $this->assertSame(['Point A', 'Point B'], $summary->summary_bullets);
        $this->assertSame('Meta description', $summary->summary_meta_description);
        $this->assertSame('fake-model', $summary->model);
        $this->assertSame('1.0.0', $summary->prompt_version);
        $this->assertSame(120, $summary->tokens_in);
        $this->assertSame(40, $summary->tokens_out);
        $this->assertNotNull($summary->generation_ms);
        $this->assertGreaterThanOrEqual(0, $summary->generation_ms);
        $this->assertSame(
            [
                ['question' => 'What?', 'answer' => 'Answer'],
                ['question' => 'Raw question', 'answer' => ''],
            ],
            $summary->summary_faq,
        );

        $this->assertDatabaseHas('content_ai_summary_events', [
            'content_id' => $content->id,
            'event' => 'completed',
        ]);
    }

    public function test_it_marks_summary_as_failed_on_invalid_ai_response(): void
    {
        $this->seed(PromptSeeder::class);

        app()->bind(AiProviderInterface::class, function () {
            return new class implements AiProviderInterface
            {
                public function generate(string $prompt, array $options = []): AiGenerationResult
                {
                    return new AiGenerationResult(
                        text: 'this is not json',
                        model: 'fake-model',
                    );
                }

                public function embed(string $input, array $options = []): array
                {
                    return [0.1, 0.2, 0.3];
                }
            };
        });

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'failed-summary',
            'title' => 'Failed Summary',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $this->expectException(AiProviderException::class);
        $this->expectExceptionMessage('not valid JSON');

        try {
            app(ContentSummaryGenerator::class)->generateForContent($content);
        } finally {
            $summary = $content->summary()->first();

            $this->assertNotNull($summary);
            $this->assertSame(SummaryStatus::FAILED, $summary?->status);
            $this->assertNotNull($summary?->last_error);
            $this->assertDatabaseHas('content_ai_summary_events', [
                'content_id' => $content->id,
                'event' => 'failed',
            ]);
        }
    }

    public function test_it_uses_map_reduce_pipeline_for_long_content(): void
    {
        $this->seed(PromptSeeder::class);
        config()->set('ai.map_reduce.enabled', true);
        config()->set('ai.map_reduce.min_body_chars', 600);
        config()->set('ai.map_reduce.chunk_chars', 700);
        config()->set('ai.map_reduce.max_chunks', 2);

        $fakeProvider = new class implements AiProviderInterface
        {
            public int $calls = 0;

            public function generate(string $prompt, array $options = []): AiGenerationResult
            {
                $this->calls++;

                if ($this->calls === 1) {
                    return new AiGenerationResult(
                        text: json_encode([
                            'key_points' => ['Map point A'],
                            'candidate_faq' => [['question' => 'Q1', 'answer' => 'A1']],
                            'candidate_tags' => ['tag-a'],
                        ]) ?: '{}',
                        model: 'map-model',
                        tokensIn: 10,
                        tokensOut: 6,
                    );
                }

                if ($this->calls === 2) {
                    return new AiGenerationResult(
                        text: json_encode([
                            'key_points' => ['Map point B'],
                            'candidate_faq' => [['question' => 'Q2', 'answer' => 'A2']],
                            'candidate_tags' => ['tag-b'],
                        ]) ?: '{}',
                        model: 'map-model',
                        tokensIn: 11,
                        tokensOut: 7,
                    );
                }

                return new AiGenerationResult(
                    text: json_encode([
                        'summary_tldr' => 'Reduced TLDR output',
                        'summary_bullets' => ['Bullet A', 'Bullet B'],
                        'summary_meta_description' => 'Reduced meta description',
                        'summary_faq' => [['question' => 'Q final', 'answer' => 'A final']],
                        'summary_tags' => ['tag-a', 'tag-b'],
                    ]) ?: '{}',
                    model: 'reduce-model',
                    tokensIn: 20,
                    tokensOut: 12,
                );
            }

            public function embed(string $input, array $options = []): array
            {
                return [0.1, 0.2, 0.3];
            }
        };

        app()->instance(AiProviderInterface::class, $fakeProvider);

        $body = str_repeat("Paragraph with enough text for map reduce chunking.\n\n", 40);

        $content = Content::create([
            'type' => ContentType::POST,
            'slug' => 'map-reduce-summary',
            'title' => 'Map Reduce Summary',
            'body' => $body,
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        $summary = app(ContentSummaryGenerator::class)->generateForContent($content);

        $this->assertSame(3, $fakeProvider->calls);
        $this->assertSame('reduce-model', $summary->model);
        $this->assertSame('mr:1.0.0+1.0.0', $summary->prompt_version);
        $this->assertSame(41, $summary->tokens_in);
        $this->assertSame(25, $summary->tokens_out);

        $event = ContentAiSummaryEvent::query()
            ->where('content_id', $content->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($event);
        $this->assertSame('completed', $event?->event);
        $this->assertSame('map-reduce', data_get($event?->meta, 'pipeline'));
        $this->assertSame(2, data_get($event?->meta, 'chunks'));
    }
}
