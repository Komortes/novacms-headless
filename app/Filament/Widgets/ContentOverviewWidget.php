<?php

namespace App\Filament\Widgets;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use Filament\Support\Icons\Heroicon;
use Filament\Widgets\StatsOverviewWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class ContentOverviewWidget extends StatsOverviewWidget
{
    protected ?string $heading = 'NovaCMS Overview';

    protected ?string $description = 'Create or update content, then run "Generate summary" from Content table actions.';

    protected ?string $pollingInterval = '15s';

    /**
     * @return array<Stat>
     */
    protected function getStats(): array
    {
        $totalContent = Content::query()->count();
        $publishedContent = Content::query()
            ->where('status', ContentStatus::PUBLISHED->value)
            ->count();
        $readySummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::READY->value)
            ->count();
        $failedSummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();

        return [
            Stat::make('Total content', $totalContent)
                ->description('All posts and pages')
                ->icon(Heroicon::DocumentText)
                ->color('gray'),
            Stat::make('Published', $publishedContent)
                ->description('Visible content')
                ->icon(Heroicon::CheckCircle)
                ->color('success'),
            Stat::make('AI ready', $readySummaries)
                ->description('Summaries ready for use')
                ->icon(Heroicon::CpuChip)
                ->color('info'),
            Stat::make('AI failed', $failedSummaries)
                ->description('Needs regeneration')
                ->icon(Heroicon::ExclamationTriangle)
                ->color('danger'),
        ];
    }
}
