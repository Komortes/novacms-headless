<?php

namespace Tests\Feature;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Data\AiGenerationResult;
use App\AI\Exceptions\AiProviderException;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
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
        $this->assertSame(
            [
                ['question' => 'What?', 'answer' => 'Answer'],
                ['question' => 'Raw question', 'answer' => ''],
            ],
            $summary->summary_faq,
        );
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
        }
    }
}

