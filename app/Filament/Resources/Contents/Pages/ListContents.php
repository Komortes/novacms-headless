<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Filament\Widgets\ContentWorkspaceWidget;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentCatalogManager;
use App\Services\ContentEmbeddingReindexer;
use App\Support\AdminPanelAccess;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Schemas\Components\Tabs\Tab;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Livewire\Attributes\On;
use Throwable;

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
            Action::make('exportBundle')
                ->label('Export bundle')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->authorize(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->visible(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->modalHeading('Export content bundle')
                ->modalSubmitActionLabel('Export')
                ->schema([
                    Select::make('type')
                        ->options([
                            'post' => 'Post',
                            'page' => 'Page',
                        ])
                        ->placeholder('All types')
                        ->native(false),
                    Select::make('status')
                        ->options([
                            'draft' => 'Draft',
                            'published' => 'Published',
                            'archived' => 'Archived',
                        ])
                        ->placeholder('All statuses')
                        ->native(false),
                    Select::make('locale')
                        ->options(Content::query()->distinct()->orderBy('locale')->pluck('locale', 'locale')->all())
                        ->placeholder('All locales')
                        ->native(false),
                    Toggle::make('with_summaries')
                        ->label('Include AI summaries')
                        ->default(true)
                        ->inline(false),
                ])
                ->action(function (array $data) {
                    $payload = app(ContentCatalogManager::class)->export(
                        type: filled($data['type'] ?? null) ? (string) $data['type'] : null,
                        status: filled($data['status'] ?? null) ? (string) $data['status'] : null,
                        locale: filled($data['locale'] ?? null) ? (string) $data['locale'] : null,
                        withSummaries: (bool) ($data['with_summaries'] ?? true),
                    );

                    return response()->streamDownload(
                        function () use ($payload): void {
                            echo json_encode(
                                $payload,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            );
                        },
                        'content-bundle-'.now()->format('Ymd-His').'.json',
                        ['Content-Type' => 'application/json'],
                    );
                }),
            Action::make('importBundle')
                ->label('Import bundle')
                ->icon(Heroicon::ArrowUpTray)
                ->color('gray')
                ->authorize(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->visible(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->modalHeading('Import content bundle')
                ->modalSubmitActionLabel('Import')
                ->modalWidth('5xl')
                ->schema([
                    Textarea::make('payload')
                        ->label('JSON payload')
                        ->rows(18)
                        ->required()
                        ->helperText('Paste an exported content bundle or a single content item.'),
                    Toggle::make('upsert')
                        ->label('Upsert existing records')
                        ->default(true)
                        ->inline(false),
                ])
                ->action(function (array $data): void {
                    try {
                        $result = app(ContentCatalogManager::class)->importFromJson(
                            payload: (string) ($data['payload'] ?? ''),
                            upsert: (bool) ($data['upsert'] ?? true),
                        );
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Import failed')
                            ->body(Str::limit($exception->getMessage(), 220))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Content imported')
                        ->body("Imported {$result['imported']} records. Created {$result['created']}, updated {$result['updated']}, skipped {$result['skipped']}.")
                        ->success()
                        ->send();
                }),
            Action::make('loadDemo')
                ->label('Load demo data')
                ->icon(Heroicon::Sparkles)
                ->color('gray')
                ->authorize(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->visible(fn (): bool => AdminPanelAccess::canManageContentCatalog())
                ->requiresConfirmation()
                ->modalDescription('Imports the bundled demo dataset into the current database.')
                ->action(function (): void {
                    $payload = File::get(database_path('seeders/data/demo-content.json'));

                    try {
                        $result = app(ContentCatalogManager::class)->importFromJson($payload, true);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Demo import failed')
                            ->body(Str::limit($exception->getMessage(), 220))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Demo dataset loaded')
                        ->body("Imported {$result['imported']} demo records.")
                        ->success()
                        ->send();
                }),
            Action::make('reindexEmbeddings')
                ->label('Reindex embeddings')
                ->icon(Heroicon::CpuChip)
                ->color('gray')
                ->authorize(fn (): bool => AdminPanelAccess::canManageEmbeddings())
                ->visible(fn (): bool => AdminPanelAccess::canManageEmbeddings())
                ->modalHeading('Queue embedding reindex')
                ->modalSubmitActionLabel('Queue reindex')
                ->schema([
                    Select::make('mode')
                        ->label('Scope')
                        ->options([
                            ContentEmbeddingReindexer::MODE_INCREMENTAL => 'Incremental only',
                            ContentEmbeddingReindexer::MODE_FULL => 'Full reindex',
                        ])
                        ->default(ContentEmbeddingReindexer::MODE_INCREMENTAL)
                        ->required()
                        ->native(false),
                    Select::make('provider')
                        ->label('Provider')
                        ->options(app(AiSettingsManager::class)->providerOptions())
                        ->placeholder('Default provider')
                        ->live()
                        ->native(false)
                        ->afterStateUpdated(function (Get $get, Set $set, mixed $state): void {
                            $provider = is_string($state) && $state !== '' ? $state : (string) config('ai.provider', 'ollama');
                            $models = app(AiSettingsManager::class)->modelOptions($provider);
                            $set('model', array_key_first($models));
                        }),
                    Select::make('model')
                        ->label('Model')
                        ->options(function (Get $get): array {
                            $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));

                            return app(AiSettingsManager::class)->modelOptions($provider);
                        })
                        ->placeholder('Default model')
                        ->searchable()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    $mode = (string) ($data['mode'] ?? ContentEmbeddingReindexer::MODE_INCREMENTAL);
                    $provider = filled($data['provider'] ?? null) ? (string) $data['provider'] : null;
                    $model = filled($data['model'] ?? null) ? (string) $data['model'] : null;

                    $reindexer = app(ContentEmbeddingReindexer::class);
                    $count = $reindexer->count($mode);

                    if ($count === 0) {
                        Notification::make()
                            ->title('Nothing to reindex')
                            ->body('No content records matched the selected embedding scope.')
                            ->warning()
                            ->send();

                        return;
                    }

                    $queued = $reindexer->dispatch($mode, $provider, $model);

                    Notification::make()
                        ->title('Embedding reindex queued')
                        ->body("Queued {$queued} records for {$mode} embedding generation.")
                        ->success()
                        ->send();
                }),
            Action::make('queueCenter')
                ->label('Queue')
                ->icon(Heroicon::Clock)
                ->url(\App\Filament\Pages\QueueCenter::getUrl())
                ->visible(fn (): bool => AdminPanelAccess::canAccessQueueOperations())
                ->color('gray'),
            Action::make('apiAccess')
                ->label('API access')
                ->icon(Heroicon::Key)
                ->url(\App\Filament\Pages\ApiAccess::getUrl())
                ->visible(fn (): bool => AdminPanelAccess::canManageApiAccess())
                ->color('gray'),
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->url(ContentResource::getUrl('faq-info'))
                ->color('gray'),
            Action::make('aiSettings')
                ->label('AI settings')
                ->icon(Heroicon::Cog6Tooth)
                ->url(\App\Filament\Pages\AiSettings::getUrl())
                ->visible(fn (): bool => AdminPanelAccess::canManageAiSettings())
                ->color('gray'),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            ContentWorkspaceWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }

    #[On('novacms-domain-event')]
    public function refreshFromDomainEvent(): void
    {
        // Trigger a re-render when background domain events are received.
    }
}
