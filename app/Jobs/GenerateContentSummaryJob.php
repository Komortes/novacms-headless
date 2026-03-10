<?php

namespace App\Jobs;

use App\DomainEvents;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryEventLogger;
use App\Services\ContentSummaryGenerator;
use App\Services\ContentSummaryQueueVersion;
use App\Services\DomainEventPublisher;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\Middleware\RateLimited;
use Illuminate\Queue\Middleware\WithoutOverlapping;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class GenerateContentSummaryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries;

    public int $timeout;

    public function __construct(
        public readonly int $contentId,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly int $version = 0,
        public readonly ?string $promptVersion = null,
    ) {
        $this->tries = max(1, (int) config('ai.jobs.summary.tries', 3));
        $this->timeout = max(30, (int) config('ai.jobs.summary.timeout', 300));
    }

    /**
     * @return array<int, object>
     */
    public function middleware(): array
    {
        return [
            (new WithoutOverlapping('summary-content-'.$this->contentId))
                ->releaseAfter(5)
                ->expireAfter($this->timeout + 30),
            new RateLimited('ai-requests'),
        ];
    }

    public function backoff(): int
    {
        return max(1, (int) config('ai.jobs.summary.backoff_seconds', 15));
    }

    public function handle(
        AiSettingsManager $aiSettingsManager,
        ContentSummaryQueueVersion $queueVersion,
        ContentSummaryEventLogger $eventLogger,
    ): void {
        $content = Content::query()
            ->with(['summary', 'summaryEvents' => fn ($query) => $query->latest('id')->limit(20)])
            ->find($this->contentId);

        if (! $content) {
            return;
        }

        // Skip outdated or cancelled queued jobs.
        if ($this->version !== $queueVersion->current($this->contentId)) {
            $eventLogger->record(
                content: $content,
                event: 'skipped',
                summary: $content->summary,
                provider: $this->provider,
                model: $this->model,
                queueVersion: $this->version,
                message: 'Outdated queue version skipped.',
            );

            return;
        }

        // Workers are long-running, so apply latest DB-backed AI config on each run.
        $aiSettingsManager->applyConfigOverrides();

        if (is_string($this->provider) && trim($this->provider) !== '') {
            config()->set('ai.provider', $this->provider);
        }

        $options = [];

        if (is_string($this->model) && trim($this->model) !== '') {
            $options['model'] = trim($this->model);
        }

        $queuedAt = $this->resolveQueuedAt($content->summaryEvents, $this->version);
        $waitMs = $queuedAt !== null ? now()->diffInMilliseconds($queuedAt) : null;
        $resolvedProvider = (string) config('ai.provider', $this->provider ?? 'ollama');
        $resolvedModel = isset($options['model']) ? (string) $options['model'] : null;

        $eventLogger->record(
            content: $content,
            event: 'started',
            summary: $content->summary,
            provider: $resolvedProvider,
            model: $resolvedModel,
            queueVersion: $this->version,
            waitMs: $waitMs,
            meta: $this->promptVersion !== null && trim($this->promptVersion) !== ''
                ? ['prompt_version' => trim($this->promptVersion)]
                : [],
        );

        /** @var ContentSummaryGenerator $generator */
        $generator = app(ContentSummaryGenerator::class);

        $generator->generateForContent(
            $content,
            $this->promptVersion,
            options: $options,
            runContext: [
                'provider' => $resolvedProvider,
                'model' => $resolvedModel,
                'queue_version' => $this->version,
                'wait_ms' => $waitMs,
                'publish_failed_event' => false,
            ],
        );
    }

    /**
     * @param  Collection<int, \App\Models\ContentAiSummaryEvent>  $events
     */
    private function resolveQueuedAt(Collection $events, int $version): ?CarbonInterface
    {
        $match = $events->first(function ($event) use ($version): bool {
            return $event->event === 'queued' && (int) ($event->queue_version ?? 0) === $version;
        });

        if (! $match) {
            return null;
        }

        return $match->created_at;
    }

    public function failed(Throwable $exception): void
    {
        $content = Content::query()->with('summary')->find($this->contentId);

        if (! $content) {
            return;
        }

        $summary = $content->summary ?: $content->summary()->firstOrCreate(
            ['content_id' => $content->id],
            ['status' => SummaryStatus::PENDING],
        );

        if ($summary->status !== SummaryStatus::READY) {
            $summary->forceFill([
                'status' => SummaryStatus::FAILED,
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();
        }

        app(ContentSummaryEventLogger::class)->record(
            content: $content,
            event: 'failed',
            summary: $summary,
            provider: $this->provider,
            model: $this->model,
            queueVersion: $this->version,
            message: 'Summary job exhausted retries: '.Str::limit($exception->getMessage(), 400),
        );

        app(DomainEventPublisher::class)->publish(DomainEvents::SUMMARY_FAILED, [
            'content_id' => $content->id,
            'summary_id' => $summary->id,
            'status' => SummaryStatus::FAILED->value,
            'model' => $this->model ?? $summary->model,
            'prompt_version' => $this->promptVersion ?? $summary->prompt_version,
            'last_error' => $summary->last_error,
            'queue_version' => $this->version,
        ]);
    }
}
