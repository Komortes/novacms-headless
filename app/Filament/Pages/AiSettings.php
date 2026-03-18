<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Services\AiSettingsManager;
use App\Services\RuntimeHealthService;
use App\Support\AdminPanelAccess;
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
use Filament\Support\Enums\Width;
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

    protected string $view = 'filament.pages.ai-settings';

    public static function canAccess(): bool
    {
        return AdminPanelAccess::canManageAiSettings();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

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

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    public function form(Schema $schema): Schema
    {
        $settingsManager = app(AiSettingsManager::class);

        return $schema
            ->columns(3)
            ->components([
                Section::make('Generation defaults')
                    ->description('Applied when no provider or model is explicitly selected from a generation action.')
                    ->columnSpan(1)
                    ->schema([
                        Select::make('default_provider')
                            ->label('Default provider')
                            ->options($settingsManager->providerOptions())
                            ->required()
                            ->native(false)
                            ->helperText('Editors can still override provider/model per run.'),
                    ]),
                Section::make('Ollama (local)')
                    ->description('Recommended for local development and low-cost demo runs.')
                    ->columnSpan(1)
                    ->schema([
                        TextInput::make('ollama_base_url')
                            ->label('Base URL')
                            ->url()
                            ->placeholder('http://127.0.0.1:11434')
                            ->helperText('Usually the local Ollama HTTP endpoint.'),
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
                    ->columnSpanFull()
                    ->schema([
                        Placeholder::make('usage_notes')
                            ->hiddenLabel()
                            ->content('Values saved here override .env defaults at runtime. Use Generate summary actions in Content pages to choose provider and model per request, while this page defines the stable operating baseline.'),
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

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $settingsManager = app(AiSettingsManager::class);
        $providers = $settingsManager->providerOptions();
        $health = app(RuntimeHealthService::class)->collect();
        $checks = collect($health['checks'])
            ->filter(fn (mixed $check): bool => is_array($check))
            ->keyBy(fn (array $check): string => strtolower((string) ($check['component'] ?? '')));
        $defaultProvider = (string) config('ai.provider', 'ollama');
        $ollamaCheck = $checks->get('ollama');
        $ollamaStatus = is_array($ollamaCheck) ? (string) ($ollamaCheck['status'] ?? 'warn') : 'warn';
        $openAiConfigured = $this->hasStoredOpenAiKey;
        $openAiStatus = $openAiConfigured ? 'ok' : ($defaultProvider === 'openai' ? 'fail' : 'warn');
        $failedChecks = $checks->where('status', 'fail')->count();
        $warningChecks = $checks->where('status', 'warn')->count();
        $runtimeAlertsCount = count((array) ($health['alerts'] ?? []));

        return [
            'providerCount' => count($providers),
            'ollamaModelCount' => count($settingsManager->modelOptions('ollama')),
            'openAiModelCount' => count($settingsManager->modelOptions('openai')),
            'storedOpenAiKey' => $this->hasStoredOpenAiKey,
            'defaultProvider' => $providers[$defaultProvider] ?? ucfirst($defaultProvider),
            'runtimeAlertsCount' => $runtimeAlertsCount,
            'failedChecks' => $failedChecks,
            'warningChecks' => $warningChecks,
            'providerCards' => [
                [
                    'label' => 'Ollama (local)',
                    'kind' => 'Local baseline',
                    'status' => $ollamaStatus,
                    'status_label' => $this->statusLabel($ollamaStatus),
                    'tone' => $this->statusTone($ollamaStatus),
                    'base_url' => (string) ($this->data['ollama_base_url'] ?? config('ai.providers.ollama.base_url') ?? 'n/a'),
                    'model' => (string) ($this->data['ollama_model'] ?? config('ai.providers.ollama.model') ?? 'n/a'),
                    'timeout' => (string) ($this->data['ollama_timeout'] ?? config('ai.providers.ollama.timeout') ?? 'n/a'),
                    'message' => is_array($ollamaCheck)
                        ? (string) ($ollamaCheck['message'] ?? 'Local runtime check unavailable.')
                        : 'Local runtime check unavailable.',
                ],
                [
                    'label' => 'OpenAI-compatible',
                    'kind' => 'External fallback',
                    'status' => $openAiStatus,
                    'status_label' => $openAiConfigured ? 'configured' : 'secret missing',
                    'tone' => $this->statusTone($openAiStatus),
                    'base_url' => (string) ($this->data['openai_base_url'] ?? config('ai.providers.openai.base_url') ?? 'n/a'),
                    'model' => (string) ($this->data['openai_model'] ?? config('ai.providers.openai.model') ?? 'n/a'),
                    'timeout' => (string) ($this->data['openai_timeout'] ?? config('ai.providers.openai.timeout') ?? 'n/a'),
                    'message' => $openAiConfigured
                        ? 'Encrypted API key is stored. External fallback is ready when local quality or latency is not enough.'
                        : ($defaultProvider === 'openai'
                            ? 'Default provider points to OpenAI-compatible runtime, but no API key is stored.'
                            : 'No external API key is stored yet. Keep this ready if you need a remote fallback.'),
                ],
            ],
            'profiles' => collect($settingsManager->profiles())
                ->map(fn (array $profile, string $key): array => [
                    'key' => $key,
                    'label' => (string) ($profile['label'] ?? ucfirst($key)),
                    'ollama_model' => (string) ($profile['models']['ollama'] ?? 'n/a'),
                    'openai_model' => (string) ($profile['models']['openai'] ?? 'n/a'),
                ])
                ->values()
                ->all(),
            'operatingLinks' => [
                [
                    'label' => 'Content Workspace',
                    'description' => 'Return to editorial flow and confirm the new baseline on a real record.',
                    'href' => ContentResource::getUrl('index'),
                    'tone' => 'indigo',
                ],
                [
                    'label' => 'Queue Center',
                    'description' => 'Watch pending and failed runs after changing providers or timeouts.',
                    'href' => QueueCenter::getUrl(),
                    'tone' => 'amber',
                ],
                [
                    'label' => 'System Health',
                    'description' => 'Check Ollama, Redis, Horizon, and runtime alerts before blaming prompts.',
                    'href' => SystemHealth::getUrl(),
                    'tone' => 'rose',
                ],
            ],
        ];
    }

    private function statusTone(string $status): string
    {
        return match ($status) {
            'ok' => 'emerald',
            'fail' => 'rose',
            default => 'amber',
        };
    }

    private function statusLabel(string $status): string
    {
        return match ($status) {
            'ok' => 'healthy',
            'fail' => 'attention required',
            default => 'degraded',
        };
    }
}
