<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
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
        $pendingAiCount = ContentAiSummary::query()
            ->whereIn('status', [SummaryStatus::PENDING->value, SummaryStatus::GENERATING->value])
            ->count();
        $failedAiCount = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();

        return [
            'totalContent' => $totalContent,
            'draftCount' => $draftCount,
            'publishedCount' => $publishedCount,
            'pendingAiCount' => $pendingAiCount,
            'failedAiCount' => $failedAiCount,
        ];
    }
}
