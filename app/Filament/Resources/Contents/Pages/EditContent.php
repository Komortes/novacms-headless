<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryDispatcher;
use App\Support\AdminPanelAccess;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Throwable;

class EditContent extends EditRecord
{
    protected static string $resource = ContentResource::class;

    protected string $view = 'filament.resources.contents.pages.edit-content';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Update content source, keep the AI pipeline healthy, and use regeneration only when the editorial intent changed.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('queueCenter')
                ->label('Queue')
                ->icon(Heroicon::Clock)
                ->color('gray')
                ->visible(fn (): bool => AdminPanelAccess::canAccessQueueOperations())
                ->url(\App\Filament\Pages\QueueCenter::getUrl()),
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->color('gray')
                ->url($this->getResourceUrl('faq-info')),
            Action::make('aiSettings')
                ->label('AI settings')
                ->icon(Heroicon::Cog6Tooth)
                ->color('gray')
                ->visible(fn (): bool => AdminPanelAccess::canManageAiSettings())
                ->url(\App\Filament\Pages\AiSettings::getUrl()),
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
                ->action(function (array $data): void {
                    /** @var Content $record */
                    $record = $this->getRecord();

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
                ->disabled(fn (): bool => $this->getRecord()->summary?->status === SummaryStatus::GENERATING),
            DeleteAction::make(),
        ];
    }

    #[On('novacms-domain-event')]
    public function refreshFromDomainEvent(): void
    {
        $this->record = $this->getRecord()->refresh();
    }

    public function quickSetStatus(string $status): void
    {
        abort_unless(AdminPanelAccess::canChangeContentStatus(), 403);

        if (! in_array($status, [
            ContentStatus::DRAFT->value,
            ContentStatus::PUBLISHED->value,
            ContentStatus::ARCHIVED->value,
        ], true)) {
            return;
        }

        /** @var Content $record */
        $record = $this->getRecord();

        try {
            $record->update([
                'status' => $status,
            ]);
            $this->record = $record->refresh();
            $this->refreshFormData(['status']);

            Notification::make()
                ->title('Status updated')
                ->body('Current status: '.$this->getRecord()->status->value)
                ->success()
                ->send();
        } catch (ValidationException $exception) {
            Notification::make()
                ->title('Publish quality gate blocked')
                ->body(collect($exception->errors())->flatten()->first() ?? 'Please regenerate summary and try again.')
                ->danger()
                ->send();
        }
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        /** @var Content $record */
        $record = $this->getRecord()->loadMissing('summary');
        $summary = $record->summary;
        $draftTitle = trim((string) data_get($this, 'data.title', $record->title));
        $draftSlug = trim((string) data_get($this, 'data.slug', $record->slug));
        $draftLocale = trim((string) data_get($this, 'data.locale', $record->locale));
        $draftBody = trim((string) data_get($this, 'data.body', $record->body));
        $draftStatus = (string) data_get($this, 'data.status', $record->status->value);
        $wordCount = count(array_filter(preg_split('/\s+/u', strip_tags((string) Str::markdown($draftBody))) ?: []));
        $readingMinutes = max(1, (int) ceil($wordCount / 220));
        $qualityGatePassed = $summary?->status === SummaryStatus::READY
            && filled($summary?->summary_tldr)
            && count($summary?->summary_bullets ?? []) > 0
            && count($summary?->summary_tags ?? []) > 0
            && count($summary?->summary_faq ?? []) > 0;

        return [
            'record' => $record,
            'summaryStatus' => $summary?->status?->value ?? SummaryStatus::PENDING->value,
            'hasReadySummary' => $summary?->status === SummaryStatus::READY,
            'bulletCount' => count($summary?->summary_bullets ?? []),
            'faqCount' => count($summary?->summary_faq ?? []),
            'tagCount' => count($summary?->summary_tags ?? []),
            'draftTitle' => $draftTitle !== '' ? $draftTitle : 'Untitled draft',
            'draftSlug' => $draftSlug !== '' ? $draftSlug : 'slug-not-set',
            'draftLocale' => $draftLocale !== '' ? $draftLocale : 'en',
            'draftStatus' => $draftStatus !== '' ? $draftStatus : ContentStatus::DRAFT->value,
            'draftBody' => $draftBody,
            'draftPreviewHtml' => (string) Str::markdown($draftBody !== '' ? $draftBody : '_Start writing to see a rendered preview._'),
            'wordCount' => $wordCount,
            'readingMinutes' => $readingMinutes,
            'qualityGatePassed' => $qualityGatePassed,
        ];
    }
}
