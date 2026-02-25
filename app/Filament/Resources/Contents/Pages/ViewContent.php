<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\AiSettingsManager;
use App\Services\ContentSummaryGenerator;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
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
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->color('gray')
                ->url($this->getResourceUrl('faq-info')),
            Action::make('aiSettings')
                ->label('AI settings')
                ->icon(Heroicon::Cog6Tooth)
                ->color('gray')
                ->url(\App\Filament\Pages\AiSettings::getUrl()),
            Action::make('generateSummary')
                ->label('Generate summary')
                ->icon(Heroicon::ArrowPath)
                ->color('info')
                ->schema([
                    Select::make('provider')
                        ->label('Provider')
                        ->options(app(AiSettingsManager::class)->providerOptions())
                        ->default(fn (): string => (string) config('ai.provider', 'ollama'))
                        ->required()
                        ->live()
                        ->native(false),
                    Select::make('model')
                        ->label('Model')
                        ->options(function (Get $get): array {
                            $provider = (string) ($get('provider') ?: config('ai.provider', 'ollama'));

                            return app(AiSettingsManager::class)->modelOptions($provider);
                        })
                        ->searchable()
                        ->native(false)
                        ->helperText('Optional: leave empty to use provider default model.'),
                    TextInput::make('custom_model')
                        ->label('Custom model id')
                        ->placeholder('e.g. qwen2.5:3b')
                        ->helperText('If filled, this value overrides the model select.'),
                ])
                ->action(function (array $data): void {
                    /** @var Content $record */
                    $record = $this->getRecord();

                    try {
                        $provider = (string) ($data['provider'] ?? config('ai.provider', 'ollama'));
                        $customModel = trim((string) ($data['custom_model'] ?? ''));
                        $selectedModel = trim((string) ($data['model'] ?? ''));
                        $model = $customModel !== '' ? $customModel : $selectedModel;

                        config()->set('ai.provider', $provider);

                        $options = [];
                        if ($model !== '') {
                            $options['model'] = $model;
                        }

                        app(ContentSummaryGenerator::class)->generateForContent($record, options: $options);
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
                        ->body('Summary refreshed with selected provider/model.')
                        ->success()
                        ->send();
                })
                ->disabled(fn (): bool => $this->getRecord()->summary?->status === SummaryStatus::GENERATING),
            EditAction::make(),
        ];
    }
}
