<?php

namespace App\Jobs;

use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryEventLogger;
use App\Services\ContentSummaryGenerator;
use App\Services\ContentSummaryQueueVersion;
use App\Services\DomainEventPublisher;
use App\DomainEvents;
use Carbon\CarbonInterface;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Collection;

class GenerateContentSummaryJob implements ShouldQueue
{
    use Dispatchable;
    use InteractsWithQueue;
    use Queueable;
    use SerializesModels;

    public int $tries = 1;

    public int $timeout = 300;

    public function __construct(
        public readonly int $contentId,
        public readonly ?string $provider = null,
        public readonly ?string $model = null,
        public readonly int $version = 0,
    ) {
    }

    public function handle(
        ContentSummaryGenerator $generator,
        AiSettingsManager $aiSettingsManager,
        ContentSummaryQueueVersion $queueVersion,
        ContentSummaryEventLogger $eventLogger,
        DomainEventPublisher $domainEventPublisher,
    ): void
    {
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

            $domainEventPublisher->publish(DomainEvents::SUMMARY_STATUS_CHANGED, [
                'content_id' => $content->id,
                'summary_id' => $content->summary?->id,
                'status' => 'skipped',
                'provider' => $this->provider,
                'model' => $this->model,
                'reason' => 'outdated_queue_version',
                'queue_version' => $this->version,
            ]);

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
        );

        $generator->generateForContent(
            $content,
            options: $options,
            runContext: [
                'provider' => $resolvedProvider,
                'model' => $resolvedModel,
                'queue_version' => $this->version,
                'wait_ms' => $waitMs,
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
}
