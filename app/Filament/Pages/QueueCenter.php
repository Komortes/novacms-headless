<?php

namespace App\Filament\Pages;

use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use App\Services\ContentSummaryDispatcher;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Carbon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Collection;
use Livewire\Attributes\On;

class QueueCenter extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Clock;

    protected static ?string $navigationLabel = 'Queue Center';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 3;

    protected static ?string $title = 'Queue Center';

    protected static ?string $slug = 'queue-center';

    protected string $view = 'filament.pages.queue-center';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Monitor summary queue and cancel queued runs before processing.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Refresh')
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->action(fn (): null => null),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $pending = ContentAiSummary::query()
            ->where('status', SummaryStatus::PENDING->value)
            ->count();

        return $pending > 0 ? (string) $pending : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $failed = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();

        return $failed > 0 ? 'danger' : 'warning';
    }

    public function cancelQueued(int $contentId): void
    {
        $content = Content::query()->find($contentId);

        if (! $content) {
            return;
        }

        app(ContentSummaryDispatcher::class)->cancelPending($content);

        Notification::make()
            ->title('Queued generation cancelled')
            ->body('Pending run for content #'.$content->id.' has been cancelled.')
            ->success()
            ->send();
    }

    #[On('novacms-domain-event')]
    public function refreshFromDomainEvent(): void
    {
        // Livewire will re-render this page after the listener invocation.
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $avgGenerationMs = (int) round((float) ContentAiSummary::query()
            ->whereNotNull('generation_ms')
            ->where('status', SummaryStatus::READY->value)
            ->limit(200)
            ->avg('generation_ms'));

        $pendingItems = Content::query()
            ->with('summary')
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::PENDING->value))
            ->orderBy('updated_at')
            ->limit(12)
            ->get();

        $generatingItems = Content::query()
            ->with('summary')
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::GENERATING->value))
            ->orderByDesc('updated_at')
            ->limit(12)
            ->get();

        $failedItems = Content::query()
            ->with('summary')
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::FAILED->value))
            ->orderByDesc('updated_at')
            ->limit(10)
            ->get();

        return [
            'pendingCount' => ContentAiSummary::query()->where('status', SummaryStatus::PENDING->value)->count(),
            'generatingCount' => ContentAiSummary::query()->where('status', SummaryStatus::GENERATING->value)->count(),
            'failedCount' => ContentAiSummary::query()->where('status', SummaryStatus::FAILED->value)->count(),
            'avgGeneration' => $this->formatDuration($avgGenerationMs > 0 ? $avgGenerationMs : null),
            'pendingItems' => $this->normalizeItems($pendingItems, 'pending', $avgGenerationMs),
            'generatingItems' => $this->normalizeItems($generatingItems, 'generating', $avgGenerationMs),
            'failedItems' => $this->normalizeItems($failedItems, 'failed', $avgGenerationMs),
        ];
    }

    /**
     * @param  Collection<int, Content>  $items
     * @return list<array{id: int, title: string, slug: string, updated: string, status: string, summary_status: string, model: string, latency: string, wait: string, elapsed: string, eta: string, queue_position: string, last_error: string}>
     */
    private function normalizeItems(Collection $items, string $bucket, int $avgGenerationMs): array
    {
        $contentIds = $items->pluck('id')->all();
        $eventsByContent = ContentAiSummaryEvent::query()
            ->whereIn('content_id', $contentIds)
            ->whereIn('event', ['queued', 'started', 'completed', 'failed', 'cancelled', 'skipped'])
            ->orderByDesc('id')
            ->get()
            ->groupBy('content_id');

        $orderedItems = $items->values();

        if ($bucket === 'pending') {
            $orderedItems = $orderedItems
                ->sortBy(function (Content $content) use ($eventsByContent): int {
                    $queuedAt = $this->resolveEventTime($eventsByContent->get($content->id, collect()), 'queued');

                    return $queuedAt?->timestamp ?? PHP_INT_MAX;
                })
                ->values();
        }

        return $orderedItems
            ->map(function (Content $content, int $index) use ($eventsByContent, $bucket, $avgGenerationMs): array {
                $events = $eventsByContent->get($content->id, collect());
                $queuedAt = $this->resolveEventTime($events, 'queued');
                $startedAt = $this->resolveEventTime($events, 'started');
                $waitMs = $queuedAt ? now()->diffInMilliseconds($queuedAt) : null;
                $elapsedMs = $startedAt ? now()->diffInMilliseconds($startedAt) : null;

                $queuePosition = $bucket === 'pending' ? '#'.($index + 1) : 'n/a';
                $etaMs = null;

                if ($avgGenerationMs > 0) {
                    if ($bucket === 'pending' && $waitMs !== null) {
                        $etaMs = max(0, (($index + 1) * $avgGenerationMs) - $waitMs);
                    }

                    if ($bucket === 'generating' && $elapsedMs !== null) {
                        $etaMs = max(0, $avgGenerationMs - $elapsedMs);
                    }
                }

                return [
                    'id' => (int) $content->id,
                    'title' => (string) $content->title,
                    'slug' => (string) $content->slug,
                    'updated' => (string) optional($content->updated_at)?->diffForHumans(),
                    'status' => (string) ($content->status->value ?? $content->status),
                    'summary_status' => (string) ($content->summary?->status?->value ?? SummaryStatus::PENDING->value),
                    'model' => (string) ($content->summary?->model ?? 'n/a'),
                    'latency' => is_numeric($content->summary?->generation_ms) ? ((int) $content->summary?->generation_ms).' ms' : 'n/a',
                    'wait' => $this->formatDuration($waitMs),
                    'elapsed' => $this->formatDuration($elapsedMs),
                    'eta' => $this->formatDuration($etaMs),
                    'queue_position' => $queuePosition,
                    'last_error' => (string) ($content->summary?->last_error ?? ''),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ContentAiSummaryEvent>  $events
     */
    private function resolveEventTime(Collection $events, string $eventName): ?Carbon
    {
        /** @var ContentAiSummaryEvent|null $event */
        $event = $events->first(fn (ContentAiSummaryEvent $candidate): bool => $candidate->event === $eventName);

        return $event?->created_at;
    }

    private function formatDuration(?int $ms): string
    {
        if (! is_numeric($ms) || (int) $ms < 0) {
            return 'n/a';
        }

        $seconds = (int) floor(((int) $ms) / 1000);

        if ($seconds < 1) {
            return '<1s';
        }

        if ($seconds < 60) {
            return $seconds.'s';
        }

        $minutes = (int) floor($seconds / 60);
        $restSeconds = $seconds % 60;

        if ($minutes < 60) {
            return sprintf('%dm %02ds', $minutes, $restSeconds);
        }

        $hours = (int) floor($minutes / 60);
        $restMinutes = $minutes % 60;

        return sprintf('%dh %02dm', $hours, $restMinutes);
    }
}
