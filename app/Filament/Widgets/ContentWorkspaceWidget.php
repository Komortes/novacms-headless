<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Pages\AiSettings;
use App\Filament\Pages\QueueCenter;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Support\AdminPanelAccess;
use Filament\Widgets\Widget;

class ContentWorkspaceWidget extends Widget
{
    protected string $view = 'filament.widgets.content-workspace-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $totalContent = Content::query()->count();
        $draftCount = Content::query()
            ->where('status', ContentStatus::DRAFT->value)
            ->count();
        $publishedCount = Content::query()
            ->where('status', ContentStatus::PUBLISHED->value)
            ->count();
        $pendingAiCount = Content::query()
            ->where(function ($query): void {
                $query
                    ->whereDoesntHave('summary')
                    ->orWhereHas('summary', fn ($summaryQuery) => $summaryQuery->where('status', SummaryStatus::PENDING->value));
            })
            ->count();
        $generatingAiCount = ContentAiSummary::query()
            ->where('status', SummaryStatus::GENERATING->value)
            ->count();
        $readyAiCount = ContentAiSummary::query()
            ->where('status', SummaryStatus::READY->value)
            ->count();
        $failedAiCount = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();
        $missingAiCount = Content::query()
            ->whereDoesntHave('summary')
            ->count();

        return [
            'totalContent' => $totalContent,
            'draftCount' => $draftCount,
            'publishedCount' => $publishedCount,
            'pendingAiCount' => $pendingAiCount,
            'generatingAiCount' => $generatingAiCount,
            'readyAiCount' => $readyAiCount,
            'failedAiCount' => $failedAiCount,
            'missingAiCount' => $missingAiCount,
            'filterRoutes' => [
                [
                    'label' => 'Draft review',
                    'description' => 'Editorial records still in progress.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'draft']),
                    'badge' => $draftCount,
                    'tone' => 'amber',
                ],
                [
                    'label' => 'AI pending',
                    'description' => 'Missing summary or queued but not started.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'ai_pending']),
                    'badge' => $pendingAiCount,
                    'tone' => 'sky',
                ],
                [
                    'label' => 'Generating',
                    'description' => 'Workers currently producing output.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'ai_generating']),
                    'badge' => $generatingAiCount,
                    'tone' => 'sky',
                ],
                [
                    'label' => 'AI ready',
                    'description' => 'Summaries ready for editorial validation.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'ai_ready']),
                    'badge' => $readyAiCount,
                    'tone' => 'emerald',
                ],
                [
                    'label' => 'AI failed',
                    'description' => 'Needs diagnosis before retry.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'ai_failed']),
                    'badge' => $failedAiCount,
                    'tone' => 'rose',
                ],
                [
                    'label' => 'Published',
                    'description' => 'Live content already in delivery.',
                    'href' => ContentResource::getUrl('index', ['tab' => 'published']),
                    'badge' => $publishedCount,
                    'tone' => 'indigo',
                ],
            ],
            'workspaceRoutes' => array_values(array_filter([
                AdminPanelAccess::canCreateContent() ? [
                    'label' => 'Create content',
                    'description' => 'Open the full create flow for a new record.',
                    'href' => ContentResource::getUrl('create'),
                    'tone' => 'indigo',
                ] : null,
                AdminPanelAccess::canAccessQueueOperations() ? [
                    'label' => 'Queue Center',
                    'description' => 'Jump to runtime backlog and failed-run diagnosis.',
                    'href' => QueueCenter::getUrl(),
                    'tone' => 'amber',
                ] : null,
                AdminPanelAccess::canManageAiSettings() ? [
                    'label' => 'AI Settings',
                    'description' => 'Adjust provider defaults and generation baseline.',
                    'href' => AiSettings::getUrl(),
                    'tone' => 'emerald',
                ] : null,
            ])),
        ];
    }
}
