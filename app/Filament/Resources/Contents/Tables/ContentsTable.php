<?php

namespace App\Filament\Resources\Contents\Tables;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\ContentSummaryGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Str;
use Throwable;

class ContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->poll('15s')
            ->striped()
            ->defaultSort('updated_at', 'desc')
            ->searchPlaceholder('Search by title or slug')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->recordUrl(fn (Content $record): string => ContentResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable(),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state)
                    ->color(fn (mixed $state): string => match ($state->value ?? (string) $state) {
                        ContentType::POST->value => 'info',
                        ContentType::PAGE->value => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state)
                    ->color(fn (mixed $state): string => match ($state->value ?? (string) $state) {
                        ContentStatus::PUBLISHED->value => 'success',
                        ContentStatus::DRAFT->value => 'warning',
                        ContentStatus::ARCHIVED->value => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('ai_status')
                    ->label('AI')
                    ->state(fn (Content $record): string => $record->summary?->status?->value ?? SummaryStatus::PENDING->value)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        SummaryStatus::READY->value => 'success',
                        SummaryStatus::GENERATING->value => 'info',
                        SummaryStatus::FAILED->value => 'danger',
                        SummaryStatus::PENDING->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('summary.summary_tldr')
                    ->label('TL;DR')
                    ->limit(70)
                    ->placeholder('Not generated yet')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->options([
                        ContentType::POST->value => 'Post',
                        ContentType::PAGE->value => 'Page',
                    ]),
                SelectFilter::make('status')
                    ->options([
                        ContentStatus::DRAFT->value => 'Draft',
                        ContentStatus::PUBLISHED->value => 'Published',
                        ContentStatus::ARCHIVED->value => 'Archived',
                    ]),
                SelectFilter::make('ai_status')
                    ->label('AI status')
                    ->options([
                        SummaryStatus::PENDING->value => 'Pending',
                        SummaryStatus::GENERATING->value => 'Generating',
                        SummaryStatus::READY->value => 'Ready',
                        SummaryStatus::FAILED->value => 'Failed',
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        $value = $data['value'] ?? null;

                        if (! $value) {
                            return $query;
                        }

                        if ($value === SummaryStatus::PENDING->value) {
                            return $query->where(function (Builder $pendingQuery): void {
                                $pendingQuery
                                    ->whereDoesntHave('summary')
                                    ->orWhereHas('summary', fn (Builder $summaryQuery) => $summaryQuery->where('status', SummaryStatus::PENDING->value));
                            });
                        }

                        return $query->whereHas(
                            'summary',
                            fn (Builder $summaryQuery): Builder => $summaryQuery->where('status', $value),
                        );
                    }),
            ])
            ->recordActions([
                Action::make('generateSummary')
                    ->label('Generate summary')
                    ->icon(Heroicon::ArrowPath)
                    ->color('info')
                    ->requiresConfirmation()
                    ->action(function (Content $record): void {
                        try {
                            app(ContentSummaryGenerator::class)->generateForContent($record);
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Summary generation failed')
                                ->body(Str::limit($exception->getMessage(), 200))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Summary generated')
                            ->success()
                            ->send();
                    })
                    ->disabled(fn (Content $record): bool => $record->summary?->status === SummaryStatus::GENERATING),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::DocumentText)
            ->emptyStateHeading('No content yet')
            ->emptyStateDescription('Create your first Post or Page, then run "Generate summary" to see AI output.');
    }
}
