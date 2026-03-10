<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryDispatcher;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\On;
use Throwable;

class ViewContent extends ViewRecord
{
    protected static string $resource = ContentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Review generated AI output and run regeneration when content changes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('queueCenter')
                ->label('Queue')
                ->icon(Heroicon::Clock)
                ->color('gray')
                ->url(\App\Filament\Pages\QueueCenter::getUrl()),
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->color('gray')
                ->url($this->getResourceUrl('faq-info')),
            Action::make('changeStatus')
                ->label('Set status')
                ->icon(Heroicon::AdjustmentsHorizontal)
                ->color('gray')
                ->schema([
                    Select::make('status')
                        ->label('Status')
                        ->options([
                            ContentStatus::DRAFT->value => 'Draft',
                            ContentStatus::PUBLISHED->value => 'Published',
                            ContentStatus::ARCHIVED->value => 'Archived',
                        ])
                        ->default(fn (): string => $this->getRecord()->status->value)
                        ->required()
                        ->native(false),
                ])
                ->action(function (array $data): void {
                    /** @var Content $record */
                    $record = $this->getRecord();

                    try {
                        $record->update([
                            'status' => $data['status'],
                        ]);
                        $this->record = $record->refresh();

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
                }),
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
            EditAction::make(),
        ];
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    #[On('novacms-domain-event')]
    public function refreshFromDomainEvent(): void
    {
        $this->record = $this->getRecord()->refresh();
    }
}
