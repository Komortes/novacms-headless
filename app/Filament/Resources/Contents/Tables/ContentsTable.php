<?php

namespace App\Filament\Resources\Contents\Tables;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryDispatcher;
use App\Support\AdminPanelAccess;
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
use Filament\Support\Enums\FontWeight;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
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
            ->heading('Editorial queue')
            ->description('Use tabs to reduce noise, row tones to spot urgency, and bulk actions for safe batched changes.')
            ->searchPlaceholder('Search title, slug, locale, or AI preview')
            ->defaultPaginationPageOption(25)
            ->paginated([25, 50, 100])
            ->recordUrl(fn (Content $record): string => ContentResource::getUrl('view', ['record' => $record]))
            ->recordClasses(fn (Content $record): array => self::recordClassesFor($record))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Content')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Content $record): string => Str::headline($record->type->value).' · '.$record->slug.' · '.strtoupper($record->locale))
                    ->wrap(),
                TextColumn::make('slug')
                    ->searchable()
                    ->copyable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('locale')
                    ->badge()
                    ->color('gray')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('type')
                    ->badge()
                    ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state)
                    ->color(fn (mixed $state): string => match ($state->value ?? (string) $state) {
                        ContentType::POST->value => 'info',
                        ContentType::PAGE->value => 'gray',
                        default => 'gray',
                    }),
                TextColumn::make('status')
                    ->label('Editorial')
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
                    ->description(function (Content $record): string {
                        $parts = [];

                        if (filled($record->summary?->model)) {
                            $parts[] = (string) $record->summary?->model;
                        }

                        if (is_numeric($record->summary?->generation_ms)) {
                            $parts[] = (int) $record->summary?->generation_ms.' ms';
                        }

                        return $parts !== [] ? implode(' · ', $parts) : 'No completed run yet';
                    })
                    ->color(fn (string $state): string => match ($state) {
                        SummaryStatus::READY->value => 'success',
                        SummaryStatus::GENERATING->value => 'info',
                        SummaryStatus::FAILED->value => 'danger',
                        SummaryStatus::PENDING->value => 'warning',
                        default => 'gray',
                    }),
                TextColumn::make('summary.summary_tldr')
                    ->label('AI Preview')
                    ->limit(96)
                    ->wrap()
                    ->placeholder('Not generated yet')
                    ->toggleable(),
                TextColumn::make('summary.generation_ms')
                    ->label('Latency')
                    ->formatStateUsing(fn (mixed $state): string => is_numeric($state) ? ((int) $state).' ms' : 'n/a')
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->description(function (Content $record): string {
                        return $record->summary?->updated_at?->diffForHumans()
                            ? 'AI '.$record->summary->updated_at->diffForHumans()
                            : 'AI not generated';
                    })
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
                SelectFilter::make('locale')
                    ->options(Content::query()->distinct()->orderBy('locale')->pluck('locale', 'locale')->all()),
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
                    ->authorize(fn (): bool => AdminPanelAccess::canQueueSummaries())
                    ->visible(fn (): bool => AdminPanelAccess::canQueueSummaries())
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

                            app(ContentSummaryDispatcher::class)->dispatch(
                                content: $record,
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
                    BulkAction::make('generateSummaries')
                        ->label('Generate AI')
                        ->icon(Heroicon::ArrowPath)
                        ->color('info')
                        ->authorize(fn (): bool => AdminPanelAccess::canQueueSummaries())
                        ->visible(fn (): bool => AdminPanelAccess::canQueueSummaries())
                        ->modalHeading('Generate summaries for selected records')
                        ->modalDescription('Queues generation for selected records that are not already generating.')
                        ->modalSubmitActionLabel('Queue generation')
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
                        ->action(function (EloquentCollection $records, array $data): void {
                            $provider = (string) ($data['provider'] ?? config('ai.provider', 'ollama'));
                            $selectedModel = trim((string) ($data['model'] ?? ''));
                            $model = $selectedModel !== '' ? $selectedModel : null;
                            $queued = 0;
                            $skipped = 0;
                            $failed = 0;

                            foreach ($records as $record) {
                                if (! $record instanceof Content) {
                                    continue;
                                }

                                if ($record->summary?->status === SummaryStatus::GENERATING) {
                                    $skipped++;

                                    continue;
                                }

                                try {
                                    app(ContentSummaryDispatcher::class)->dispatch(
                                        content: $record,
                                        provider: $provider,
                                        model: $model,
                                    );

                                    $queued++;
                                } catch (Throwable) {
                                    $failed++;
                                }
                            }

                            Notification::make()
                                ->title('Bulk generation completed')
                                ->body("Queued {$queued}, skipped {$skipped}, failed {$failed}.")
                                ->{$failed > 0 ? 'warning' : 'success'}()
                                ->send();
                        }),
                    self::statusBulkAction(
                        name: 'markPublished',
                        status: ContentStatus::PUBLISHED,
                        label: 'Publish',
                        icon: Heroicon::CheckCircle,
                        color: 'success',
                    ),
                    self::statusBulkAction(
                        name: 'markDraft',
                        status: ContentStatus::DRAFT,
                        label: 'Move to draft',
                        icon: Heroicon::PencilSquare,
                        color: 'warning',
                    ),
                    self::statusBulkAction(
                        name: 'markArchived',
                        status: ContentStatus::ARCHIVED,
                        label: 'Archive',
                        icon: Heroicon::ArchiveBox,
                        color: 'gray',
                    ),
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => AdminPanelAccess::canDeleteContent()),
                ]),
            ])
            ->emptyStateIcon(Heroicon::DocumentText)
            ->emptyStateHeading('No content yet')
            ->emptyStateDescription('Create your first Post or Page, then use tabs and bulk actions to manage AI generation at scale.')
            ->emptyStateActions([
                Action::make('createContent')
                    ->label('Create content')
                    ->button()
                    ->icon(Heroicon::Plus)
                    ->url(ContentResource::getUrl('create'))
                    ->visible(fn (): bool => AdminPanelAccess::canCreateContent()),
            ]);
    }

    /**
     * @return array<string, bool>
     */
    private static function recordClassesFor(Content $record): array
    {
        $summaryStatus = $record->summary?->status?->value;
        $editorialStatus = $record->status instanceof ContentStatus ? $record->status->value : (string) $record->status;

        return [
            '!bg-rose-50/75 dark:!bg-rose-950/15' => $summaryStatus === SummaryStatus::FAILED->value,
            '!bg-sky-50/75 dark:!bg-sky-950/15' => $summaryStatus === SummaryStatus::GENERATING->value,
            '!bg-amber-50/75 dark:!bg-amber-950/15' => ($summaryStatus === SummaryStatus::PENDING->value) || blank($summaryStatus),
            '!bg-emerald-50/75 dark:!bg-emerald-950/15' => ($summaryStatus === SummaryStatus::READY->value) && ($editorialStatus === ContentStatus::DRAFT->value),
        ];
    }

    private static function statusBulkAction(
        string $name,
        ContentStatus $status,
        string $label,
        Heroicon|string $icon,
        string $color,
    ): BulkAction {
        return BulkAction::make($name)
            ->label($label)
            ->icon($icon)
            ->color($color)
            ->authorize(fn (): bool => AdminPanelAccess::canChangeContentStatus())
            ->visible(fn (): bool => AdminPanelAccess::canChangeContentStatus())
            ->requiresConfirmation()
            ->deselectRecordsAfterCompletion()
            ->action(function (EloquentCollection $records) use ($status): void {
                $updated = 0;

                foreach ($records as $record) {
                    if (! $record instanceof Content) {
                        continue;
                    }

                    $currentStatus = $record->status instanceof ContentStatus ? $record->status : ContentStatus::from((string) $record->status);

                    if ($currentStatus === $status) {
                        continue;
                    }

                    $record->update([
                        'status' => $status,
                    ]);

                    $updated++;
                }

                Notification::make()
                    ->title('Statuses updated')
                    ->body("Moved {$updated} records to {$status->value}.")
                    ->{$updated > 0 ? 'success' : 'warning'}()
                    ->send();
            });
    }
}
