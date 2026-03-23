<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Pages\AiSettings;
use App\Filament\Pages\ApiAccess;
use App\Filament\Pages\QueueCenter;
use App\Filament\Pages\SystemHealth;
use App\Filament\Resources\Contents\ContentResource;
use App\Filament\Resources\Prompts\PromptResource;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\ContentAiSummaryEvent;
use App\Models\Prompt;
use App\Services\DemoWorkspaceService;
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
        $demoReport = app(DemoWorkspaceService::class)->report(withRuntime: false);

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
            'demoReport' => $demoReport,
            'demoWalkthrough' => $this->demoWalkthrough(),
            'demoSignals' => $this->demoSignals($demoReport),
            'demoOperations' => $this->demoOperations(),
            'operatingSignals' => $this->operatingSignals(
                publishedContent: $publishedContent,
                reviewReadyCount: $reviewReadyCount,
                missingSummaryCount: $missingSummaryCount,
                pendingSummaries: $pendingSummaries,
                failedSummaries: $failedSummaries,
                alertsCount: $alertsCount,
                activePrompts: $activePrompts,
            ),
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
            $user?->canManageApiAccess() => 'Own the headless CMS operating baseline: prompts, providers, tokens, secrets, and recovery paths.',
            $user?->canAccessQueueOperations() => 'Protect AI summarization throughput before editors retry content or delivery flows.',
            default => 'Move markdown content through review, validate AI summaries, and publish only when headless delivery is ready.',
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
                'description' => 'Open content operations, AI summarization status, and editorial actions.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
            ],
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'Queue Center',
                'description' => 'Inspect pending, generating, and failed AI summarization work.',
                'href' => QueueCenter::getUrl(),
                'tone' => 'amber',
            ] : null,
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'System Health',
                'description' => 'Check the headless CMS runtime: Postgres, Redis, Horizon, Reverb, and Ollama.',
                'href' => SystemHealth::getUrl(),
                'tone' => 'rose',
            ] : null,
            AdminPanelAccess::canManagePrompts() ? [
                'label' => 'Prompt Registry',
                'description' => 'Manage prompt governance for AI summarization and compare live contracts.',
                'href' => PromptResource::getUrl('index'),
                'tone' => 'sky',
            ] : null,
            AdminPanelAccess::canManageAiSettings() ? [
                'label' => 'AI Settings',
                'description' => 'Tune the AI runtime baseline: providers, models, timeouts, and fallback keys.',
                'href' => AiSettings::getUrl(),
                'tone' => 'emerald',
            ] : null,
        ];

        return array_values(array_filter($links));
    }

    /**
     * @return list<array{title: string, description: string, href: string, tone: string}>
     */
    private function demoWalkthrough(): array
    {
        $readyContent = Content::query()
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::READY->value))
            ->orderByDesc('updated_at')
            ->first();
        $draftContent = Content::query()
            ->where('status', ContentStatus::DRAFT->value)
            ->orderByDesc('updated_at')
            ->first();
        $failedContent = Content::query()
            ->whereHas('summary', fn ($query) => $query->where('status', SummaryStatus::FAILED->value))
            ->orderByDesc('updated_at')
            ->first();

        if (AdminPanelAccess::canManageApiAccess()) {
            return [
                [
                    'title' => 'Open the content workspace',
                    'description' => 'Start with the seeded records so the editorial, ready, and failed AI states are visible immediately.',
                    'href' => ContentResource::getUrl('index'),
                    'tone' => 'indigo',
                ],
                [
                    'title' => 'Review a ready article',
                    'description' => 'Open a published record with TL;DR, bullets, FAQ, and tags already generated.',
                    'href' => $this->contentViewUrl($readyContent),
                    'tone' => 'emerald',
                ],
                [
                    'title' => 'Inspect the failed draft',
                    'description' => 'Use the seeded failed item to explain retries, queue visibility, and operator handoff.',
                    'href' => $failedContent ? $this->contentViewUrl($failedContent) : QueueCenter::getUrl(),
                    'tone' => 'amber',
                ],
                [
                    'title' => 'Issue a GraphQL token',
                    'description' => 'Open API Access and create a token so the headless delivery story is not theoretical.',
                    'href' => ApiAccess::getUrl(),
                    'tone' => 'sky',
                ],
            ];
        }

        if (AdminPanelAccess::canAccessQueueOperations() && ! AdminPanelAccess::canCreateContent()) {
            return [
                [
                    'title' => 'Open queue center',
                    'description' => 'Start from the runtime lane and show where pending, generating, and failed work accumulates.',
                    'href' => QueueCenter::getUrl(),
                    'tone' => 'amber',
                ],
                [
                    'title' => 'Inspect the failed draft',
                    'description' => 'Read the failed item in context so operators can tie queue errors back to real content.',
                    'href' => $this->contentViewUrl($failedContent),
                    'tone' => 'rose',
                ],
                [
                    'title' => 'Check system health',
                    'description' => 'Validate Redis, Horizon, Reverb, and Ollama before asking for retries.',
                    'href' => SystemHealth::getUrl(),
                    'tone' => 'sky',
                ],
            ];
        }

        return [
            [
                'title' => 'Open the content workspace',
                'description' => 'Use the seeded bundle to explain draft, published, and AI status lanes without creating anything first.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
            ],
            [
                'title' => 'Review a ready article',
                'description' => 'Open a record with usable AI output and show how summaries support editorial decisions.',
                'href' => $this->contentViewUrl($readyContent),
                'tone' => 'emerald',
            ],
            [
                'title' => 'Edit the draft example',
                'description' => 'Use the draft record to explain how content moves toward publication after review.',
                'href' => $this->contentEditUrl($draftContent),
                'tone' => 'amber',
            ],
        ];
    }

    /**
     * @param  array{
     *     summary: array{
     *         demo_users_found: int,
     *         demo_users_expected: int,
     *         content: int,
     *         published: int,
     *         drafts: int,
     *         ready_summaries: int,
     *         failed_summaries: int,
     *         active_prompts: int
     *     }
     * }  $demoReport
     * @return list<array{label: string, value: string, tone: string}>
     */
    private function demoSignals(array $demoReport): array
    {
        $summary = $demoReport['summary'];

        return [
            [
                'label' => 'Demo users',
                'value' => $summary['demo_users_found'].'/'.$summary['demo_users_expected'],
                'tone' => $summary['demo_users_found'] === $summary['demo_users_expected'] ? 'emerald' : 'rose',
            ],
            [
                'label' => 'Seeded content',
                'value' => $summary['content'].' records',
                'tone' => $summary['content'] >= 4 ? 'indigo' : 'rose',
            ],
            [
                'label' => 'Published / Draft',
                'value' => $summary['published'].' / '.$summary['drafts'],
                'tone' => ($summary['published'] > 0 && $summary['drafts'] > 0) ? 'sky' : 'rose',
            ],
            [
                'label' => 'AI seed state',
                'value' => $summary['ready_summaries'].' ready · '.$summary['failed_summaries'].' failed',
                'tone' => ($summary['ready_summaries'] > 0 && $summary['failed_summaries'] > 0) ? 'amber' : 'rose',
            ],
        ];
    }

    /**
     * @return list<array{command: string, description: string, tone: string}>
     */
    private function demoOperations(): array
    {
        return [
            [
                'command' => 'make demo-check',
                'description' => 'Validate runtime sockets plus the seeded users, content states, and active prompt baseline before a walkthrough. Missing live models are reported without blocking the seeded demo.',
                'tone' => 'emerald',
            ],
            [
                'command' => 'make demo-reset',
                'description' => 'Restore the reference dataset so the published examples and failed draft are back in their expected state.',
                'tone' => 'amber',
            ],
            [
                'command' => 'make demo-models',
                'description' => 'Pull local Ollama models only when you want to run fresh generation after the seeded walkthrough.',
                'tone' => 'sky',
            ],
            [
                'command' => 'make demo-token',
                'description' => 'Print a read-only GraphQL token so a frontend consumer or API client can query the seeded content immediately.',
                'tone' => 'emerald',
            ],
            [
                'command' => 'make demo-logs',
                'description' => 'Tail the Docker demo stack when startup, queue processing, or runtime services need inspection.',
                'tone' => 'indigo',
            ],
        ];
    }

    /**
     * @return list<array{title: string, value: string|int, description: string, href: string, tone: string}>
     */
    private function operatingSignals(
        int $publishedContent,
        int $reviewReadyCount,
        int $missingSummaryCount,
        int $pendingSummaries,
        int $failedSummaries,
        int $alertsCount,
        int $activePrompts,
    ): array {
        $editorialValue = match (true) {
            $reviewReadyCount > 0 => $reviewReadyCount.' ready',
            $missingSummaryCount > 0 => $missingSummaryCount.' missing AI',
            default => 'calm',
        };

        $runtimeValue = match (true) {
            $alertsCount > 0 => $alertsCount.' alerts',
            $failedSummaries > 0 => $failedSummaries.' failed',
            $pendingSummaries > 0 => $pendingSummaries.' in flight',
            default => 'stable',
        };

        $governanceValue = match (true) {
            AdminPanelAccess::canManagePrompts() || AdminPanelAccess::canManageAiSettings() => $activePrompts.' active',
            default => $publishedContent.' live',
        };

        return [
            [
                'title' => 'Editorial readiness',
                'value' => $editorialValue,
                'description' => $reviewReadyCount > 0
                    ? 'Drafts already have AI summary material and are ready for human review.'
                    : 'Start with missing summaries before expecting a clean editorial lane.',
                'href' => ContentResource::getUrl('index'),
                'tone' => $reviewReadyCount > 0 ? 'emerald' : 'amber',
            ],
            [
                'title' => 'Runtime posture',
                'value' => $runtimeValue,
                'description' => $alertsCount > 0 || $failedSummaries > 0
                    ? 'Check queue and runtime health before retrying records or blaming content.'
                    : 'The AI summarization pipeline is stable enough for normal content operations.',
                'href' => AdminPanelAccess::canAccessQueueOperations() ? QueueCenter::getUrl() : ContentResource::getUrl('index'),
                'tone' => $alertsCount > 0 || $failedSummaries > 0 ? 'rose' : ($pendingSummaries > 0 ? 'sky' : 'emerald'),
            ],
            [
                'title' => 'Baseline contract',
                'value' => $governanceValue,
                'description' => AdminPanelAccess::canManagePrompts() || AdminPanelAccess::canManageAiSettings()
                    ? 'Prompt governance and provider defaults define the production AI contract.'
                    : 'Your role consumes the AI baseline through review rather than changing it.',
                'href' => AdminPanelAccess::canManagePrompts()
                    ? PromptResource::getUrl('index')
                    : (AdminPanelAccess::canManageAiSettings() ? AiSettings::getUrl() : ContentResource::getUrl('index')),
                'tone' => AdminPanelAccess::canManagePrompts() || AdminPanelAccess::canManageAiSettings() ? 'sky' : 'indigo',
            ],
        ];
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
                'description' => 'Move markdown content toward headless delivery while identifying which records still need first-run AI summarization.',
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
                'description' => 'Prompt governance and provider defaults shape AI summarization quality more than one-off retries.',
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
                'description' => 'Editors should treat generated summaries, bullets, and FAQ blocks as draft material for headless content, not final truth.',
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
                'description' => 'Use the content workspace to validate TL;DR, bullets, FAQ, and tags before headless delivery.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'emerald',
            ],
            [
                'title' => $pendingSummaries > 0 ? $pendingSummaries.' AI runs are still in flight' : 'Queue pressure is calm',
                'description' => 'Pending and generating AI summarization runs are easiest to understand from Queue Center.',
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

    private function contentViewUrl(?Content $content): string
    {
        return $content
            ? ContentResource::getUrl('view', ['record' => $content])
            : ContentResource::getUrl('index');
    }

    private function contentEditUrl(?Content $content): string
    {
        return $content
            ? ContentResource::getUrl('edit', ['record' => $content])
            : ContentResource::getUrl('index');
    }
}
