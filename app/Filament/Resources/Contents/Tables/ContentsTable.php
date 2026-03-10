<?php

namespace App\Filament\Resources\Contents\Tables;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentBulkOperations;
use Filament\Actions\Action;
use Filament\Actions\BulkAction;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
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
                TextColumn::make('summary.generation_ms')
                    ->label('Latency')
                    ->formatStateUsing(fn (mixed $state): string => is_numeric($state) ? ((int) $state).' ms' : 'n/a')
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
                    ->modalHeading('Generate summary')
                    ->modalSubmitActionLabel('Queue generation')
                    ->schema([
                        Select::make('provider')
                            ->label('Provider')
                            ->options(app(AiSettingsManager::class)->providerOptions())
                            ->default(fn (): string => (string) config('ai.provider', 'ollama'))
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                $profile = (string) ($get('generation_profile') ?: 'balanced');
                                $model = app(AiSettingsManager::class)->modelForProfile((string) $state, $profile);

                                if ($model !== null) {
                                    $set('model', $model);
                                }
                            })
                            ->native(false),
                        Select::make('generation_profile')
                            ->label('Preset')
                            ->options(app(AiSettingsManager::class)->profileOptions())
                            ->default('balanced')
                            ->required()
                            ->live()
                            ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));
                                $model = app(AiSettingsManager::class)->modelForProfile($provider, is_string($state) ? $state : null);

                                if ($model !== null) {
                                    $set('model', $model);
                                }
                            })
                            ->native(false),
                        Select::make('model')
                            ->label('Model')
                            ->options(function (Get $get): array {
                                $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));

                                return app(AiSettingsManager::class)->modelOptions($provider);
                            })
                            ->default(function (Get $get): ?string {
                                $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));
                                $profile = (string) ($get('generation_profile') ?: 'balanced');

                                return app(AiSettingsManager::class)->modelForProfile($provider, $profile);
                            })
                            ->searchable()
                            ->native(false)
                            ->helperText('Preset auto-fills model.'),
                    ])
                    ->action(function (array $data, Content $record): void {
                        try {
                            $provider = (string) ($data['provider'] ?? config('ai.provider', 'ollama'));
                            $selectedModel = trim((string) ($data['model'] ?? ''));
                            $model = $selectedModel;

                            app(ContentBulkOperations::class)->queueSummaries(
                                [$record],
                                provider: $provider,
                                model: $model !== '' ? $model : null,
                            );
                        } catch (Throwable $exception) {
                            Notification::make()
                                ->title('Failed to queue generation')
                                ->body(Str::limit($exception->getMessage(), 200))
                                ->danger()
                                ->send();

                            return;
                        }

                        Notification::make()
                            ->title('Generation queued')
                            ->body('Summary will update automatically when worker finishes.')
                            ->success()
                            ->send();
                    })
                    ->disabled(fn (Content $record): bool => $record->summary?->status === SummaryStatus::GENERATING),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    BulkAction::make('queueSummaries')
                        ->label('Generate summaries')
                        ->icon(Heroicon::ArrowPath)
                        ->color('info')
                        ->modalHeading('Generate summaries')
                        ->modalDescription('Queue summary generation for the selected records. Records already generating are skipped.')
                        ->modalSubmitActionLabel('Queue selected')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            Select::make('provider')
                                ->label('Provider')
                                ->options(app(AiSettingsManager::class)->providerOptions())
                                ->default(fn (): string => (string) config('ai.provider', 'ollama'))
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                    $profile = (string) ($get('generation_profile') ?: 'balanced');
                                    $model = app(AiSettingsManager::class)->modelForProfile((string) $state, $profile);

                                    if ($model !== null) {
                                        $set('model', $model);
                                    }
                                })
                                ->native(false),
                            Select::make('generation_profile')
                                ->label('Preset')
                                ->options(app(AiSettingsManager::class)->profileOptions())
                                ->default('balanced')
                                ->required()
                                ->live()
                                ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                                    $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));
                                    $model = app(AiSettingsManager::class)->modelForProfile($provider, is_string($state) ? $state : null);

                                    if ($model !== null) {
                                        $set('model', $model);
                                    }
                                })
                                ->native(false),
                            Select::make('model')
                                ->label('Model')
                                ->options(function (Get $get): array {
                                    $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));

                                    return app(AiSettingsManager::class)->modelOptions($provider);
                                })
                                ->default(function (Get $get): ?string {
                                    $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));
                                    $profile = (string) ($get('generation_profile') ?: 'balanced');

                                    return app(AiSettingsManager::class)->modelForProfile($provider, $profile);
                                })
                                ->searchable()
                                ->native(false)
                                ->helperText('Preset auto-fills model.'),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records): void {
                            try {
                                $result = app(ContentBulkOperations::class)->queueSummaries(
                                    $records,
                                    provider: (string) ($data['provider'] ?? config('ai.provider', 'ollama')),
                                    model: filled($data['model'] ?? null) ? (string) $data['model'] : null,
                                );
                            } catch (Throwable $exception) {
                                Notification::make()
                                    ->title('Failed to queue selected summaries')
                                    ->body(Str::limit($exception->getMessage(), 200))
                                    ->danger()
                                    ->send();

                                return;
                            }

                            Notification::make()
                                ->title('Summary generation queued')
                                ->body(sprintf(
                                    'Queued %d record(s). Skipped %d already generating record(s).',
                                    $result['queued'],
                                    $result['skipped'],
                                ))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('reindexEmbeddings')
                        ->label('Reindex embeddings')
                        ->icon(Heroicon::CpuChip)
                        ->color('gray')
                        ->modalHeading('Reindex embeddings')
                        ->modalDescription('Queue embedding regeneration for the selected records using the configured embeddings provider and model.')
                        ->modalSubmitActionLabel('Queue selected')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->action(function (\Illuminate\Database\Eloquent\Collection $records): void {
                            $result = app(ContentBulkOperations::class)->queueEmbeddings($records);

                            Notification::make()
                                ->title('Embeddings queued')
                                ->body(sprintf('Queued embedding reindex for %d record(s).', $result['queued']))
                                ->success()
                                ->send();
                        }),
                    BulkAction::make('changeStatus')
                        ->label('Change status')
                        ->icon(Heroicon::AdjustmentsHorizontal)
                        ->color('warning')
                        ->modalHeading('Change status')
                        ->modalDescription('Apply one content status to all selected records. Publish will still respect the quality gate.')
                        ->modalSubmitActionLabel('Update selected')
                        ->requiresConfirmation()
                        ->deselectRecordsAfterCompletion()
                        ->schema([
                            Select::make('status')
                                ->label('Status')
                                ->options([
                                    ContentStatus::DRAFT->value => 'Draft',
                                    ContentStatus::PUBLISHED->value => 'Published',
                                    ContentStatus::ARCHIVED->value => 'Archived',
                                ])
                                ->required()
                                ->native(false),
                        ])
                        ->action(function (array $data, \Illuminate\Database\Eloquent\Collection $records): void {
                            $result = app(ContentBulkOperations::class)->updateStatuses(
                                $records,
                                (string) $data['status'],
                            );

                            $body = sprintf(
                                'Updated %d record(s). Skipped %d unchanged record(s).',
                                $result['updated'],
                                $result['skipped'],
                            );

                            if ($result['failed'] > 0) {
                                $body .= ' Failed: '.$result['failed'].'. '.Str::limit(implode(' | ', $result['errors']), 220);
                            }

                            Notification::make()
                                ->title($result['failed'] > 0 ? 'Status update completed with warnings' : 'Status updated')
                                ->body($body)
                                ->{$result['failed'] > 0 ? 'warning' : 'success'}()
                                ->send();
                        }),
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateIcon(Heroicon::DocumentText)
            ->emptyStateHeading('No content yet')
            ->emptyStateDescription('Create your first Post or Page, then run "Generate summary" to see AI output.');
    }
}
