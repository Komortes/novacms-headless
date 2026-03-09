<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentEmbedding;
use Database\Seeders\PromptSeeder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Throwable;

class RuntimeE2eSmokeService
{
    public function __construct(
        private readonly RuntimeHealthService $healthService,
        private readonly PromptRegistry $promptRegistry,
        private readonly ContentSummaryDispatcher $summaryDispatcher,
        private readonly ContentEmbeddingDispatcher $embeddingDispatcher,
        private readonly QueueWorkerRunner $queueWorkerRunner,
        private readonly SemanticSearchService $semanticSearchService,
    ) {
    }

    /**
     * @param  array{
     *     provider?: string|null,
     *     summary_model?: string|null,
     *     embedding_provider?: string|null,
     *     embedding_model?: string|null,
     *     prompt_version?: string|null,
     *     keep_records?: bool,
     *     require_horizon?: bool,
     *     require_reverb?: bool,
     *     timeout_seconds?: int
     * }  $options
     * @return array{
     *     ok: bool,
     *     steps: list<array{name: string, status: string, message: string, meta: array<string, mixed>}>,
     *     content_id: int|null,
     *     slug: string|null,
     *     generated_at: string
     * }
     */
    public function run(array $options = []): array
    {
        $steps = [];
        $content = null;
        $slug = null;
        $keepRecords = (bool) ($options['keep_records'] ?? false);
        $timeoutSeconds = max(5, (int) ($options['timeout_seconds'] ?? 60));
        $summaryQueue = 'ai-smoke-summary-'.Str::lower(Str::random(6));
        $embeddingQueue = 'ai-smoke-embedding-'.Str::lower(Str::random(6));
        $originalSummaryQueue = (string) config('ai.jobs.summary.queue', 'ai');
        $originalEmbeddingQueue = (string) config('ai.jobs.embeddings.queue', 'ai');
        $originalSummaryAutoDispatch = (bool) config('ai.summary.auto_dispatch', true);
        $originalEmbeddingsAutoDispatch = (bool) config('ai.embeddings.auto_dispatch', true);
        $originalEmbeddingsProvider = config('ai.embeddings.provider');
        $originalEmbeddingsModel = config('ai.embeddings.model');

        try {
            $healthReport = $this->healthService->collect();
            $this->assertRequiredHealth(
                $healthReport['checks'],
                (bool) ($options['require_horizon'] ?? false),
                (bool) ($options['require_reverb'] ?? false),
            );

            $steps[] = $this->step('health', 'ok', 'Required runtime components are reachable.', [
                'checks' => collect($healthReport['checks'])
                    ->mapWithKeys(fn (array $check): array => [$check['component'] => $check['status']])
                    ->all(),
            ]);

            $this->ensurePromptDefaults();
            $steps[] = $this->step('prompts', 'ok', 'Default summary prompts are available.', [
                'active_prompt' => $this->promptRegistry->resolveActive(
                    'content.summary',
                    $this->normalizeOption($options['prompt_version'] ?? null),
                )->version,
            ]);

            config()->set('ai.summary.auto_dispatch', false);
            config()->set('ai.embeddings.auto_dispatch', false);
            config()->set('ai.jobs.summary.queue', $summaryQueue);
            config()->set('ai.jobs.embeddings.queue', $embeddingQueue);

            $embeddingProvider = $this->normalizeOption($options['embedding_provider'] ?? null)
                ?? $this->normalizeOption($options['provider'] ?? null);
            $embeddingModel = $this->normalizeOption($options['embedding_model'] ?? null);

            if ($embeddingProvider !== null) {
                config()->set('ai.embeddings.provider', $embeddingProvider);
            }

            if ($embeddingModel !== null) {
                config()->set('ai.embeddings.model', $embeddingModel);
            }

            $slug = 'smoke-'.Str::lower(Str::random(12));
            $content = Content::query()->create([
                'type' => ContentType::POST,
                'slug' => $slug,
                'title' => 'Runtime Smoke Content '.$slug,
                'body' => implode("\n\n", [
                    'NovaCMS smoke content used to validate the live async content pipeline.',
                    'This record should produce an AI summary, create embeddings in pgvector, and resolve through semantic search.',
                    'Keywords: live stack validation, semantic search smoke test, queued summary generation.',
                ]),
                'locale' => 'en',
                'status' => ContentStatus::PUBLISHED,
            ]);

            $steps[] = $this->step('content', 'ok', 'Smoke content record created.', [
                'content_id' => $content->id,
                'slug' => $content->slug,
            ]);

            $summaryProvider = $this->normalizeOption($options['provider'] ?? null);
            $summaryModel = $this->normalizeOption($options['summary_model'] ?? null);
            $promptVersion = $this->normalizeOption($options['prompt_version'] ?? null);

            $this->summaryDispatcher->dispatch(
                content: $content,
                provider: $summaryProvider,
                model: $summaryModel,
                promptVersion: $promptVersion,
            );

            $summary = $this->awaitSummary($content, $summaryQueue, $timeoutSeconds);
            $steps[] = $this->step('summary', 'ok', 'Queued summary completed through Redis worker flow.', [
                'summary_id' => $summary->id,
                'status' => $summary->status->value,
                'model' => $summary->model,
                'prompt_version' => $summary->prompt_version,
            ]);

            $this->embeddingDispatcher->dispatch(
                content: $content,
                provider: $embeddingProvider,
                model: $embeddingModel,
            );

            $embeddingCount = $this->awaitEmbeddings($content, $embeddingQueue, $timeoutSeconds);
            $steps[] = $this->step('embeddings', 'ok', 'Queued embeddings completed and persisted.', [
                'chunks' => $embeddingCount,
                'provider' => $embeddingProvider ?? (string) config('ai.embeddings.provider'),
                'model' => $embeddingModel ?? (string) config('ai.embeddings.model'),
            ]);

            $matches = collect($this->semanticSearchService->semanticSearch(
                query: 'live stack validation semantic search smoke test',
                limit: 3,
                locale: $content->locale,
                status: ContentStatus::PUBLISHED,
                type: $content->type,
            ));

            $matched = $matches->first(fn (array $match): bool => (int) $match['content']->id === $content->id);

            if (! is_array($matched)) {
                throw new \RuntimeException('Semantic search did not return the smoke content.');
            }

            $steps[] = $this->step('search', 'ok', 'Semantic search resolved the generated content.', [
                'score' => round((float) $matched['score'], 4),
                'rank' => $matches->search(fn (array $match): bool => (int) $match['content']->id === $content->id) + 1,
            ]);

            $ok = true;
        } catch (Throwable $exception) {
            $steps[] = $this->step('failure', 'failed', $exception->getMessage());
            $ok = false;
        } finally {
            config()->set('ai.jobs.summary.queue', $originalSummaryQueue);
            config()->set('ai.jobs.embeddings.queue', $originalEmbeddingQueue);
            config()->set('ai.summary.auto_dispatch', $originalSummaryAutoDispatch);
            config()->set('ai.embeddings.auto_dispatch', $originalEmbeddingsAutoDispatch);
            config()->set('ai.embeddings.provider', $originalEmbeddingsProvider);
            config()->set('ai.embeddings.model', $originalEmbeddingsModel);

            if ($content instanceof Model && ! $keepRecords) {
                try {
                    $content->delete();
                    $steps[] = $this->step('cleanup', 'ok', 'Smoke content and dependent records removed.', [
                        'content_id' => $content->id,
                    ]);
                } catch (Throwable $exception) {
                    $steps[] = $this->step('cleanup', 'warning', 'Smoke cleanup failed: '.$exception->getMessage(), [
                        'content_id' => $content->id,
                    ]);
                }
            }
        }

        return [
            'ok' => $ok,
            'steps' => $steps,
            'content_id' => $keepRecords ? $content?->id : null,
            'slug' => $keepRecords ? $slug : null,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @param  list<array{component: string, status: string, message: string, meta: array<string, mixed>}>  $checks
     */
    private function assertRequiredHealth(array $checks, bool $requireHorizon, bool $requireReverb): void
    {
        $required = ['Database', 'Redis', 'Ollama'];

        if ($requireHorizon) {
            $required[] = 'Horizon';
        }

        if ($requireReverb) {
            $required[] = 'Reverb';
        }

        $indexed = collect($checks)->keyBy('component');

        foreach ($required as $component) {
            $check = $indexed->get($component);

            if (! is_array($check) || ($check['status'] ?? 'failed') !== 'ok') {
                throw new \RuntimeException("Required runtime component is not healthy: {$component}.");
            }
        }
    }

    private function ensurePromptDefaults(): void
    {
        if ($this->promptRegistry->findActive('content.summary') !== null) {
            return;
        }

        app(PromptSeeder::class)->run();
    }

    private function awaitSummary(Content $content, string $queue, int $timeoutSeconds): Model
    {
        $deadline = now()->addSeconds($timeoutSeconds);
        $connection = (string) config('queue.default', 'redis');

        while (now()->lt($deadline)) {
            $summary = $content->summary()->first();

            if ($summary?->status === SummaryStatus::READY) {
                return $summary;
            }

            if ($summary?->status === SummaryStatus::FAILED) {
                throw new \RuntimeException('Summary generation failed: '.($summary->last_error ?? 'Unknown error.'));
            }

            $result = $this->queueWorkerRunner->runOnce($connection, $queue);

            if ($result['exit_code'] !== 0) {
                throw new \RuntimeException('Queue worker failed during summary smoke step: '.$result['output']);
            }

            $content->refresh();
        }

        throw new \RuntimeException('Timed out waiting for queued summary generation to complete.');
    }

    private function awaitEmbeddings(Content $content, string $queue, int $timeoutSeconds): int
    {
        $deadline = now()->addSeconds($timeoutSeconds);
        $connection = (string) config('queue.default', 'redis');

        while (now()->lt($deadline)) {
            $embeddingCount = ContentEmbedding::query()
                ->where('content_id', $content->id)
                ->where('content_hash', $content->content_hash)
                ->count();

            if ($embeddingCount > 0) {
                return $embeddingCount;
            }

            $result = $this->queueWorkerRunner->runOnce($connection, $queue);

            if ($result['exit_code'] !== 0) {
                throw new \RuntimeException('Queue worker failed during embeddings smoke step: '.$result['output']);
            }
        }

        throw new \RuntimeException('Timed out waiting for queued embeddings generation to complete.');
    }

    /**
     * @return array{name: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function step(string $name, string $status, string $message, array $meta = []): array
    {
        return [
            'name' => $name,
            'status' => $status,
            'message' => $message,
            'meta' => $meta,
        ];
    }

    private function normalizeOption(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $trimmed = trim($value);

        return $trimmed !== '' ? $trimmed : null;
    }
}
