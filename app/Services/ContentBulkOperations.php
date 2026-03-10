<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummaryEvent;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

class ContentBulkOperations
{
    public function __construct(
        private readonly ContentSummaryDispatcher $summaryDispatcher,
        private readonly ContentEmbeddingDispatcher $embeddingDispatcher,
    ) {
    }

    /**
     * @param  iterable<int, Content>  $contents
     * @return array{queued: int, skipped: int}
     */
    public function queueSummaries(iterable $contents, ?string $provider = null, ?string $model = null): array
    {
        $records = $this->normalizeContents($contents);
        $queued = 0;
        $skipped = 0;

        foreach ($records as $content) {
            if ($content->summary?->status === SummaryStatus::GENERATING) {
                $skipped++;

                continue;
            }

            $context = $this->latestSummaryDispatchContextFor($content);

            $this->summaryDispatcher->dispatch(
                content: $content,
                provider: $provider ?? $context['provider'],
                model: $model ?? $context['model'],
            );

            $queued++;
        }

        return [
            'queued' => $queued,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  iterable<int, Content>  $contents
     * @return array{queued: int}
     */
    public function queueEmbeddings(iterable $contents, ?string $provider = null, ?string $model = null): array
    {
        $queued = 0;

        foreach ($this->normalizeContents($contents) as $content) {
            $this->embeddingDispatcher->dispatch($content, $provider, $model);
            $queued++;
        }

        return [
            'queued' => $queued,
        ];
    }

    /**
     * @param  iterable<int, Content>  $contents
     * @return array{retried: int, skipped: int}
     */
    public function retryFailedSummaries(iterable $contents): array
    {
        $retried = 0;
        $skipped = 0;

        foreach ($this->normalizeContents($contents) as $content) {
            if ($content->summary?->status !== SummaryStatus::FAILED) {
                $skipped++;

                continue;
            }

            $context = $this->latestSummaryDispatchContextFor($content);

            $this->summaryDispatcher->dispatch(
                content: $content,
                provider: $context['provider'],
                model: $context['model'],
            );

            $retried++;
        }

        return [
            'retried' => $retried,
            'skipped' => $skipped,
        ];
    }

    /**
     * @param  iterable<int, Content>  $contents
     * @return array{updated: int, skipped: int, failed: int, errors: list<string>}
     */
    public function updateStatuses(iterable $contents, ContentStatus|string $status): array
    {
        $targetStatus = $status instanceof ContentStatus ? $status : ContentStatus::from((string) $status);
        $updated = 0;
        $skipped = 0;
        $failed = 0;
        $errors = [];

        foreach ($this->normalizeContents($contents) as $content) {
            if ($content->status === $targetStatus) {
                $skipped++;

                continue;
            }

            try {
                $content->update([
                    'status' => $targetStatus,
                ]);

                $updated++;
            } catch (ValidationException $exception) {
                $failed++;
                $errors[] = sprintf(
                    '#%d %s: %s',
                    $content->id,
                    $content->slug,
                    collect($exception->errors())->flatten()->first() ?? 'Status update failed.',
                );
            }
        }

        return [
            'updated' => $updated,
            'skipped' => $skipped,
            'failed' => $failed,
            'errors' => $errors,
        ];
    }

    /**
     * @param  iterable<int, Content>  $contents
     * @return Collection<int, Content>
     */
    private function normalizeContents(iterable $contents): Collection
    {
        return collect($contents)
            ->filter(fn (mixed $content): bool => $content instanceof Content)
            ->values();
    }

    /**
     * @return array{provider: ?string, model: ?string}
     */
    private function latestSummaryDispatchContextFor(Content $content): array
    {
        $event = ContentAiSummaryEvent::query()
            ->where('content_id', $content->id)
            ->where(function ($query): void {
                $query
                    ->whereNotNull('provider')
                    ->orWhereNotNull('model');
            })
            ->latest('id')
            ->first();

        return [
            'provider' => filled($event?->provider) ? (string) $event->provider : null,
            'model' => filled($event?->model)
                ? (string) $event->model
                : (filled($content->summary?->model) ? (string) $content->summary?->model : null),
        ];
    }
}
