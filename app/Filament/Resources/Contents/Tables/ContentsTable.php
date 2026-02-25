<?php

namespace App\Filament\Resources\Contents\Tables;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\ContentSummaryGenerator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;
use Throwable;

class ContentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->sortable(),
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
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
