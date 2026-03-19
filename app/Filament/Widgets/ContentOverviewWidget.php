<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Pages\AiSettings;
use App\Filament\Pages\QueueCenter;
use App\Filament\Pages\SystemHealth;
use App\Filament\Resources\Contents\ContentResource;
use App\Filament\Resources\Prompts\PromptResource;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use App\Models\Prompt;
use App\Services\RuntimeHealthService;
use App\Support\AdminPanelAccess;
use Filament\Widgets\Widget;

class ContentOverviewWidget extends Widget
{
    protected string $view = 'filament.widgets.content-overview-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -3;

    public static function canView(): bool
    {
        return AdminPanelAccess::canAccessPanel();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $totalContent = Content::query()->count();
        $publishedContent = Content::query()
            ->where('status', ContentStatus::PUBLISHED->value)
            ->count();
        $draftContent = Content::query()
            ->where('status', ContentStatus::DRAFT->value)
            ->count();
        $readySummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::READY->value)
            ->count();
        $pendingSummaries = ContentAiSummary::query()
            ->whereIn('status', [SummaryStatus::PENDING->value, SummaryStatus::GENERATING->value])
            ->count();
        $failedSummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();
        $activePrompts = Prompt::query()
            ->where('is_active', true)
            ->count();
        $alerts = app(RuntimeHealthService::class)->queueAlerts();
        $alertsCount = count($alerts);
        $reviewReadyCount = Content::query()
            ->where('status', ContentStatus::DRAFT->value)
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::READY->value))
            ->count();
        $missingSummaryCount = Content::query()
            ->whereDoesntHave('summary')
            ->count();
        $recentContent = Content::query()
            ->with('summary')
            ->latest('updated_at')
            ->limit(6)
            ->get()
            ->map(fn (Content $content): array => [
                'id' => (int) $content->id,
                'title' => (string) $content->title,
                'slug' => (string) $content->slug,
                'status' => (string) ($content->status->value ?? $content->status),
                'summary_status' => (string) ($content->summary?->status?->value ?? 'missing'),
                'updated_at' => $content->updated_at?->diffForHumans() ?? 'n/a',
                'href' => ContentResource::getUrl('view', ['record' => $content]),
            ])
            ->all();
        $recentEvents = ContentAiSummaryEvent::query()
            ->with('content')
            ->latest('id')
            ->limit(6)
            ->get()
            ->map(fn (ContentAiSummaryEvent $event): array => [
                'event' => (string) $event->event,
                'title' => (string) ($event->content?->title ?? 'Unknown content'),
                'model' => (string) ($event->model ?? 'n/a'),
                'provider' => (string) ($event->provider ?? 'n/a'),
                'message' => (string) ($event->message ?? ''),
                'updated_at' => $event->created_at?->diffForHumans() ?? 'n/a',
                'href' => $event->content ? ContentResource::getUrl('view', ['record' => $event->content]) : null,
            ])
            ->all();

        return [
            'totalContent' => $totalContent,
            'publishedContent' => $publishedContent,
            'draftContent' => $draftContent,
            'readySummaries' => $readySummaries,
            'pendingSummaries' => $pendingSummaries,
            'failedSummaries' => $failedSummaries,
            'activePrompts' => $activePrompts,
            'alertsCount' => $alertsCount,
            'reviewReadyCount' => $reviewReadyCount,
            'missingSummaryCount' => $missingSummaryCount,
            'alerts' => $alerts,
            'roleLabel' => AdminPanelAccess::user()?->roleLabel() ?? 'Workspace',
            'roleFocus' => $this->roleFocus(),
            'quickLinks' => $this->quickLinks(),
            'workflowLanes' => $this->workflowLanes(
                draftContent: $draftContent,
                publishedContent: $publishedContent,
                reviewReadyCount: $reviewReadyCount,
                missingSummaryCount: $missingSummaryCount,
                pendingSummaries: $pendingSummaries,
                failedSummaries: $failedSummaries,
                alertsCount: $alertsCount,
                activePrompts: $activePrompts,
            ),
            'attentionItems' => $this->attentionItems(
                reviewReadyCount: $reviewReadyCount,
                pendingSummaries: $pendingSummaries,
                failedSummaries: $failedSummaries,
                alertsCount: $alertsCount,
                missingSummaryCount: $missingSummaryCount,
            ),
            'recentContent' => $recentContent,
            'recentEvents' => $recentEvents,
        ];
    }

    private function roleFocus(): string
    {
        $user = AdminPanelAccess::user();

        return match (true) {
            $user?->canManageApiAccess() => 'Own the operating baseline: prompts, providers, tokens, secrets, and recovery paths.',
            $user?->canAccessQueueOperations() => 'Prioritize queue lag, failed runs, and runtime health before retrying content.',
            default => 'Keep drafts clean, validate AI output, and publish only after the quality gate is satisfied.',
        };
    }

    /**
     * @return list<array{label: string, description: string, href: string, tone: string}>
     */
    private function quickLinks(): array
    {
        $links = [
            [
                'label' => 'Content Workspace',
                'description' => 'Open editorial flow, list filters, and generation actions.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
            ],
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'Queue Center',
                'description' => 'Inspect pending, generating, and failed summary work.',
                'href' => QueueCenter::getUrl(),
                'tone' => 'amber',
            ] : null,
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'System Health',
                'description' => 'Check Redis, Horizon, Reverb, Ollama, and queue alerts.',
                'href' => SystemHealth::getUrl(),
                'tone' => 'rose',
            ] : null,
            AdminPanelAccess::canManagePrompts() ? [
                'label' => 'Prompt Registry',
                'description' => 'Manage active prompt contracts and compare versions.',
                'href' => PromptResource::getUrl('index'),
                'tone' => 'sky',
            ] : null,
            AdminPanelAccess::canManageAiSettings() ? [
                'label' => 'AI Settings',
                'description' => 'Tune provider defaults, models, timeouts, and API keys.',
                'href' => AiSettings::getUrl(),
                'tone' => 'emerald',
            ] : null,
        ];

        return array_values(array_filter($links));
    }

    /**
     * @return list<array{eyebrow: string, title: string, description: string, href: string, tone: string, cta: string, stats: list<array{label: string, value: string|int}>}>
     */
    private function workflowLanes(
        int $draftContent,
        int $publishedContent,
        int $reviewReadyCount,
        int $missingSummaryCount,
        int $pendingSummaries,
        int $failedSummaries,
        int $alertsCount,
        int $activePrompts,
    ): array {
        $lanes = [
            [
                'eyebrow' => 'Editorial lane',
                'title' => 'Move drafts toward publish',
                'description' => 'Keep review-ready content moving while identifying which records still need first-run generation.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
                'cta' => 'Open content workspace',
                'stats' => [
                    ['label' => 'Drafts', 'value' => $draftContent],
                    ['label' => 'Review ready', 'value' => $reviewReadyCount],
                    ['label' => 'Missing AI', 'value' => $missingSummaryCount],
                ],
            ],
            AdminPanelAccess::canAccessQueueOperations() ? [
                'eyebrow' => 'Runtime lane',
                'title' => 'Keep the queue honest',
                'description' => 'Inspect backlog, failed runs, and active alerts before asking editors to retry content manually.',
                'href' => QueueCenter::getUrl(),
                'tone' => 'amber',
                'cta' => 'Open queue center',
                'stats' => [
                    ['label' => 'In flight', 'value' => $pendingSummaries],
                    ['label' => 'Failed', 'value' => $failedSummaries],
                    ['label' => 'Alerts', 'value' => $alertsCount],
                ],
            ] : [
                'eyebrow' => 'AI lane',
                'title' => 'Track generation backlog',
                'description' => 'Even without queue permissions, editorial quality still depends on which records have usable AI output.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'sky',
                'cta' => 'Review content statuses',
                'stats' => [
                    ['label' => 'Review ready', 'value' => $reviewReadyCount],
                    ['label' => 'Missing AI', 'value' => $missingSummaryCount],
                    ['label' => 'Failed AI', 'value' => $failedSummaries],
                ],
            ],
            (AdminPanelAccess::canManagePrompts() || AdminPanelAccess::canManageAiSettings()) ? [
                'eyebrow' => 'Governance lane',
                'title' => 'Protect the operating baseline',
                'description' => 'Prompt contracts and provider defaults shape output quality more than one-off retries.',
                'href' => AdminPanelAccess::canManagePrompts() ? PromptResource::getUrl('index') : AiSettings::getUrl(),
                'tone' => 'sky',
                'cta' => AdminPanelAccess::canManagePrompts() ? 'Open prompt registry' : 'Open AI settings',
                'stats' => [
                    ['label' => 'Active prompts', 'value' => $activePrompts],
                    ['label' => 'Runtime alerts', 'value' => $alertsCount],
                    ['label' => 'Failed AI', 'value' => $failedSummaries],
                ],
            ] : [
                'eyebrow' => 'Quality lane',
                'title' => 'Use AI output as draft material',
                'description' => 'Editors should treat generated summaries, bullets, and FAQ blocks as inputs to review, not final truth.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'emerald',
                'cta' => 'Open review queue',
                'stats' => [
                    ['label' => 'Drafts', 'value' => $draftContent],
                    ['label' => 'Review ready', 'value' => $reviewReadyCount],
                    ['label' => 'Published', 'value' => $publishedContent],
                ],
            ],
        ];

        return array_values(array_filter($lanes));
    }

    /**
     * @return list<array{title: string, description: string, href: string, tone: string}>
     */
    private function attentionItems(
        int $reviewReadyCount,
        int $pendingSummaries,
        int $failedSummaries,
        int $alertsCount,
        int $missingSummaryCount,
    ): array {
        $items = [
            [
                'title' => $reviewReadyCount > 0 ? $reviewReadyCount.' drafts are ready for review' : 'No review-ready drafts right now',
                'description' => 'Use the content workspace to validate TL;DR, bullets, FAQ, and tags before publishing.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'emerald',
            ],
            [
                'title' => $pendingSummaries > 0 ? $pendingSummaries.' AI runs are still in flight' : 'Queue pressure is calm',
                'description' => 'Pending and generating runs are easiest to understand from Queue Center.',
                'href' => AdminPanelAccess::canAccessQueueOperations() ? QueueCenter::getUrl() : ContentResource::getUrl('index'),
                'tone' => 'sky',
            ],
            [
                'title' => $failedSummaries > 0 ? $failedSummaries.' failed runs need diagnosis' : 'No failed summary runs are waiting',
                'description' => 'Read the error first. Re-run only after the runtime and prompt contract make sense.',
                'href' => AdminPanelAccess::canAccessQueueOperations() ? QueueCenter::getUrl() : ContentResource::getUrl('index'),
                'tone' => 'rose',
            ],
            [
                'title' => $alertsCount > 0 ? $alertsCount.' runtime alerts are active' : 'Runtime alerts are clear',
                'description' => $missingSummaryCount > 0
                    ? $missingSummaryCount.' records still have no stored summary and may need first-run generation.'
                    : 'Infrastructure and content pipeline look stable enough for normal editorial work.',
                'href' => AdminPanelAccess::canAccessQueueOperations() ? SystemHealth::getUrl() : ContentResource::getUrl('index'),
                'tone' => 'amber',
            ],
        ];

        return $items;
    }
}
