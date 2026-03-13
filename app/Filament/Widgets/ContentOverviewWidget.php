<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
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
        $alertsCount = count(app(RuntimeHealthService::class)->queueAlerts());

        return [
            'totalContent' => $totalContent,
            'publishedContent' => $publishedContent,
            'draftContent' => $draftContent,
            'readySummaries' => $readySummaries,
            'pendingSummaries' => $pendingSummaries,
            'failedSummaries' => $failedSummaries,
            'activePrompts' => $activePrompts,
            'alertsCount' => $alertsCount,
            'roleLabel' => AdminPanelAccess::user()?->roleLabel() ?? 'Workspace',
            'roleFocus' => $this->roleFocus(),
            'quickLinks' => $this->quickLinks(),
        ];
    }

    private function roleFocus(): string
    {
        $user = AdminPanelAccess::user();

        return match (true) {
            $user?->canManageApiAccess() => 'Own the operating baseline: prompts, providers, secrets, and recovery paths.',
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
                'href' => \App\Filament\Resources\Contents\ContentResource::getUrl('index'),
                'tone' => 'indigo',
            ],
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'Queue Center',
                'description' => 'Inspect pending, generating, and failed summary work.',
                'href' => \App\Filament\Pages\QueueCenter::getUrl(),
                'tone' => 'amber',
            ] : null,
            AdminPanelAccess::canAccessQueueOperations() ? [
                'label' => 'System Health',
                'description' => 'Check Redis, Horizon, Reverb, Ollama, and queue alerts.',
                'href' => \App\Filament\Pages / SystemHealth::getUrl(),
                'tone' => 'rose',
            ] : null,
            AdminPanelAccess::canManagePrompts() ? [
                'label' => 'Prompt Registry',
                'description' => 'Manage active prompt contracts and compare versions.',
                'href' => \App\Filament\Resources\Prompts\PromptResource::getUrl('index'),
                'tone' => 'sky',
            ] : null,
            AdminPanelAccess::canManageAiSettings() ? [
                'label' => 'AI Settings',
                'description' => 'Tune provider defaults, models, timeouts, and API keys.',
                'href' => \App\Filament\Pages\AiSettings::getUrl(),
                'tone' => 'emerald',
            ] : null,
        ];

        return array_values(array_filter($links));
    }
}
