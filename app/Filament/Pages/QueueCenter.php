<?php

namespace App\Filament\Pages;

use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use App\Services\ContentSummaryDispatcher;
use App\Services\RuntimeHealthService;
use App\Support\AdminPanelAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Carbon;
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

    public static function canAccess(): bool
    {
        return AdminPanelAccess::canAccessQueueOperations();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Monitor summary queue and cancel queued runs before processing.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
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
        $pendingCount = ContentAiSummary::query()->where('status', SummaryStatus::PENDING->value)->count();
        $generatingCount = ContentAiSummary::query()->where('status', SummaryStatus::GENERATING->value)->count();
        $failedCount = ContentAiSummary::query()->where('status', SummaryStatus::FAILED->value)->count();
        $queueDepth = $pendingCount + $generatingCount;
        $oldestPendingSummary = ContentAiSummary::query()
            ->where('status', SummaryStatus::PENDING->value)
            ->oldest('updated_at')
            ->first();
        $oldestPendingAgeMs = $oldestPendingSummary?->updated_at?->diffInMilliseconds(now());
        $windowHours = 24;
        $windowStart = now()->subHours($windowHours);
        $alerts = app(RuntimeHealthService::class)->queueAlerts();
        $lagThresholdMinutes = max(1, (int) config('ops.alerts.queue_lag_minutes_threshold', 15));
        $stalePendingCount = ContentAiSummary::query()
            ->where('status', SummaryStatus::PENDING->value)
            ->where('updated_at', '<=', now()->subMinutes($lagThresholdMinutes))
            ->count();
        $recentCompleted = ContentAiSummaryEvent::query()
            ->where('event', 'completed')
            ->where('created_at', '>=', $windowStart)
            ->count();
        $recentFailed = ContentAiSummaryEvent::query()
            ->where('event', 'failed')
            ->where('created_at', '>=', $windowStart)
            ->count();
        $recentTotal = $recentCompleted + $recentFailed;
        $successRate = $recentTotal > 0 ? round(($recentCompleted / $recentTotal) * 100, 1) : null;
        $failureRate = $recentTotal > 0 ? round(($recentFailed / $recentTotal) * 100, 1) : null;

        $avgGenerationMs = (int) round((float) ContentAiSummary::query()
            ->whereNotNull('generation_ms')
            ->where('status', SummaryStatus::READY->value)
            ->latest('id')
            ->limit(200)
            ->pluck('generation_ms')
            ->avg());

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
            'pendingCount' => $pendingCount,
            'generatingCount' => $generatingCount,
            'failedCount' => $failedCount,
            'queueDepth' => $queueDepth,
            'oldestPendingAge' => $this->formatDuration($oldestPendingAgeMs),
            'windowHours' => $windowHours,
            'recentCompletedCount' => $recentCompleted,
            'recentFailedCount' => $recentFailed,
            'recentSuccessRate' => $successRate,
            'recentFailureRate' => $failureRate,
            'avgGeneration' => $this->formatDuration($avgGenerationMs > 0 ? $avgGenerationMs : null),
            'alerts' => $alerts,
            'lagThresholdMinutes' => $lagThresholdMinutes,
            'stalePendingCount' => $stalePendingCount,
            'pressureState' => $this->pressureState(
                queueDepth: $queueDepth,
                failedCount: $failedCount,
                alertsCount: count($alerts),
                stalePendingCount: $stalePendingCount,
            ),
            'bucketMix' => $this->bucketMix(
                pendingCount: $pendingCount,
                generatingCount: $generatingCount,
                failedCount: $failedCount,
                recentCompletedCount: $recentCompleted,
                stalePendingCount: $stalePendingCount,
                lagThresholdMinutes: $lagThresholdMinutes,
            ),
            'recommendedRoutes' => $this->recommendedRoutes(
                pendingCount: $pendingCount,
                failedCount: $failedCount,
                alertsCount: count($alerts),
            ),
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

    /**
     * @return array{tone: string, label: string, description: string}
     */
    private function pressureState(int $queueDepth, int $failedCount, int $alertsCount, int $stalePendingCount): array
    {
        if ($alertsCount > 0 || $stalePendingCount > 0) {
            return [
                'tone' => 'rose',
                'label' => 'Intervention',
                'description' => 'Queue lag or failure growth is already visible. Stabilize runtime conditions before adding retries.',
            ];
        }

        if ($queueDepth > 0 || $failedCount > 0) {
            return [
                'tone' => 'amber',
                'label' => 'Watch Closely',
                'description' => 'Workers are active, but backlog or failures still deserve operator attention.',
            ];
        }

        return [
            'tone' => 'emerald',
            'label' => 'Steady',
            'description' => 'Queue is flowing normally. Use this page as confirmation, not a place to intervene.',
        ];
    }

    /**
     * @return list<array{label: string, value: string|int, description: string, tone: string}>
     */
    private function bucketMix(
        int $pendingCount,
        int $generatingCount,
        int $failedCount,
        int $recentCompletedCount,
        int $stalePendingCount,
        int $lagThresholdMinutes,
    ): array {
        return [
            [
                'label' => 'Pending lane',
                'value' => $pendingCount,
                'description' => $stalePendingCount > 0
                    ? $stalePendingCount.' jobs are older than '.$lagThresholdMinutes.'m.'
                    : 'Jobs waiting for worker pickup.',
                'tone' => 'amber',
            ],
            [
                'label' => 'Generating lane',
                'value' => $generatingCount,
                'description' => 'Active worker time currently in progress.',
                'tone' => 'sky',
            ],
            [
                'label' => 'Failed lane',
                'value' => $failedCount,
                'description' => 'Runs that need diagnosis before any retry.',
                'tone' => 'rose',
            ],
            [
                'label' => 'Recent throughput',
                'value' => $recentCompletedCount,
                'description' => 'Completed runs captured in the last 24h.',
                'tone' => 'emerald',
            ],
        ];
    }

    /**
     * @return list<array{label: string, description: string, href: string, tone: string, badge: string}>
     */
    private function recommendedRoutes(int $pendingCount, int $failedCount, int $alertsCount): array
    {
        $routes = [
            [
                'label' => $failedCount > 0 ? 'Review impacted content' : 'Return to content workspace',
                'description' => $failedCount > 0
                    ? 'Open the records behind failed runs and decide whether the issue belongs to content, prompts, or runtime.'
                    : 'Go back to the editorial queue once runtime pressure is under control.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
                'badge' => $failedCount > 0 ? $failedCount.' failed' : max($pendingCount, 0).' queued',
            ],
            [
                'label' => 'Check system health',
                'description' => 'Validate Redis, Horizon, Reverb, and Ollama before canceling or re-queuing more work.',
                'href' => SystemHealth::getUrl(),
                'tone' => $alertsCount > 0 ? 'rose' : 'sky',
                'badge' => $alertsCount > 0 ? $alertsCount.' alerts' : 'runtime',
            ],
            AdminPanelAccess::canManageAiSettings() ? [
                'label' => 'Adjust AI baseline',
                'description' => 'Tune provider defaults, models, and timeouts if the backlog pattern points to provider mismatch.',
                'href' => AiSettings::getUrl(),
                'tone' => 'emerald',
                'badge' => 'settings',
            ] : null,
        ];

        return array_values(array_filter($routes));
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
