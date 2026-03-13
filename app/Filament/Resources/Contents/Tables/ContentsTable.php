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
            ->searchPlaceholder('Search title, slug, or locale')
            ->defaultPaginationPageOption(10)
            ->paginated([10, 25, 50])
            ->recordUrl(fn (Content $record): string => ContentResource::getUrl('view', ['record' => $record]))
            ->columns([
                TextColumn::make('id')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('title')
                    ->label('Content')
                    ->searchable()
                    ->sortable()
                    ->weight(FontWeight::SemiBold)
                    ->description(fn (Content $record): string => $record->slug.' · '.$record->locale)
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
                    ->label('TL;DR')
                    ->limit(110)
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
                    DeleteBulkAction::make()
                        ->visible(fn (): bool => AdminPanelAccess::canDeleteContent()),
                ]),
            ])
            ->emptyStateIcon(Heroicon::DocumentText)
            ->emptyStateHeading('No content yet')
            ->emptyStateDescription('Create your first Post or Page, then run "Generate summary" to see AI output.');
    }
}
