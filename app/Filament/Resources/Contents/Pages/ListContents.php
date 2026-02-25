<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;

class ListContents extends ListRecords
{
    protected static string $resource = ContentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Compact content operations: filter by tabs, open a record, then run AI generation.';
    }

    public function getTabs(): array
    {
        return [
            'all' => Tab::make('All')
                ->badge(Content::query()->count()),
            'draft' => Tab::make('Draft')
                ->badge(Content::query()->where('status', ContentStatus::DRAFT->value)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ContentStatus::DRAFT->value)),
            'published' => Tab::make('Published')
                ->badge(Content::query()->where('status', ContentStatus::PUBLISHED->value)->count())
                ->modifyQueryUsing(fn (Builder $query): Builder => $query->where('status', ContentStatus::PUBLISHED->value)),
            'ai_pending' => Tab::make('AI Pending')
                ->badge(
                    Content::query()
                        ->where(function (Builder $query): void {
                            $query
                                ->whereDoesntHave('summary')
                                ->orWhereHas('summary', fn (Builder $summaryQuery) => $summaryQuery->where('status', SummaryStatus::PENDING->value));
                        })
                        ->count(),
                )
                ->modifyQueryUsing(function (Builder $query): Builder {
                    return $query->where(function (Builder $pendingQuery): void {
                        $pendingQuery
                            ->whereDoesntHave('summary')
                            ->orWhereHas('summary', fn (Builder $summaryQuery) => $summaryQuery->where('status', SummaryStatus::PENDING->value));
                    });
                }),
            'ai_failed' => Tab::make('AI Failed')
                ->badge(
                    Content::query()
                        ->whereHas('summary', fn (Builder $summaryQuery) => $summaryQuery->where('status', SummaryStatus::FAILED->value))
                        ->count(),
                )
                ->modifyQueryUsing(
                    fn (Builder $query): Builder => $query->whereHas(
                        'summary',
                        fn (Builder $summaryQuery): Builder => $summaryQuery->where('status', SummaryStatus::FAILED->value),
                    ),
                ),
        ];
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->url(ContentResource::getUrl('faq-info'))
                ->color('gray'),
            CreateAction::make(),
        ];
    }
}
