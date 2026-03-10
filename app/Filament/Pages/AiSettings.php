<?php

namespace App\Filament\Pages;

use App\Services\AiSettingsManager;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class AiSettings extends Page
{
    /**
     * @var array<string, mixed> | null
     */
    public ?array $data = [];

    public bool $hasStoredOpenAiKey = false;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::Cog6Tooth;

    protected static ?string $navigationLabel = 'AI Settings';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 2;

    protected static ?string $title = 'AI Settings';

    protected static ?string $slug = 'settings/ai';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Configure provider defaults and API credentials used by AI summary generation.';
    }

    public function mount(): void
    {
        $this->fillForm();
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        $settingsManager = app(AiSettingsManager::class);

        return $schema
            ->columns(2)
            ->components([
                Section::make('Generation defaults')
                    ->description('Used when no provider/model is explicitly selected in generation actions.')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('default_provider')
                            ->label('Default provider')
                            ->options($settingsManager->providerOptions())
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Ollama (local)')
                    ->description('Recommended for zero-cost local runs.')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('ollama_base_url')
                            ->label('Base URL')
                            ->url()
                            ->placeholder('http://127.0.0.1:11434'),
                        Select::make('ollama_model')
                            ->label('Default model')
                            ->options($settingsManager->modelOptions('ollama'))
                            ->searchable()
                            ->native(false)
                            ->helperText('Light models are preferred for local machines with low memory.'),
                        TextInput::make('ollama_timeout')
                            ->label('Timeout (seconds)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(600),
                    ]),
                Section::make('OpenAI-compatible provider')
                    ->description('Supports OpenAI and compatible APIs via custom base URL.')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('openai_key_status')
                            ->label('Stored API key')
                            ->content(fn (): string => $this->hasStoredOpenAiKey ? 'Saved in database (encrypted).' : 'Not configured.'),
                        TextInput::make('openai_base_url')
                            ->label('Base URL')
                            ->url()
                            ->placeholder('https://api.openai.com/v1'),
                        Select::make('openai_model')
                            ->label('Default model')
                            ->options($settingsManager->modelOptions('openai'))
                            ->searchable()
                            ->native(false),
                        TextInput::make('openai_timeout')
                            ->label('Timeout (seconds)')
                            ->numeric()
                            ->minValue(5)
                            ->maxValue(600),
                        TextInput::make('openai_api_key')
                            ->label('API key')
                            ->password()
                            ->revealable(filament()->arePasswordsRevealable())
                            ->autocomplete('new-password')
                            ->disabled(fn (Get $get): bool => (bool) $get('clear_openai_api_key'))
                            ->helperText('Leave empty to keep current key.'),
                        Toggle::make('clear_openai_api_key')
                            ->label('Clear saved API key'),
                    ]),
                Section::make('Notes')
                    ->compact()
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('usage_notes')
                            ->hiddenLabel()
                            ->content('Values saved here override .env defaults at runtime. Use Generate summary actions in Content pages to choose provider/model per request.'),
                    ]),
            ]);
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Form::make([EmbeddedSchema::make('form')])
                    ->id('form')
                    ->livewireSubmitHandler('save')
                    ->footer([
                        Actions::make([
                            Action::make('save')
                                ->label('Save settings')
                                ->submit('save')
                                ->keyBindings(['mod+s']),
                        ]),
                    ]),
            ]);
    }

    public function save(): void
    {
        /** @var array<string, mixed> $data */
        $data = $this->form->getState();

        app(AiSettingsManager::class)->save($data);

        $this->hasStoredOpenAiKey = app(AiSettingsManager::class)->hasStoredOpenAiKey();
        $this->data['openai_api_key'] = null;
        $this->data['clear_openai_api_key'] = false;

        Notification::make()
            ->title('AI settings saved')
            ->success()
            ->send();
    }

    protected function fillForm(): void
    {
        $settingsManager = app(AiSettingsManager::class);
        $settings = $settingsManager->getSettings();

        $this->hasStoredOpenAiKey = filled($settings?->openai_api_key);

        $this->form->fill([
            'default_provider' => $settings?->default_provider ?? config('ai.provider', 'ollama'),
            'ollama_base_url' => $settings?->ollama_base_url ?? config('ai.providers.ollama.base_url'),
            'ollama_model' => $settings?->ollama_model ?? config('ai.providers.ollama.model'),
            'ollama_timeout' => $settings?->ollama_timeout ?? config('ai.providers.ollama.timeout'),
            'openai_base_url' => $settings?->openai_base_url ?? config('ai.providers.openai.base_url'),
            'openai_model' => $settings?->openai_model ?? config('ai.providers.openai.model'),
            'openai_timeout' => $settings?->openai_timeout ?? config('ai.providers.openai.timeout'),
            'openai_api_key' => null,
            'clear_openai_api_key' => false,
        ]);
    }
}
