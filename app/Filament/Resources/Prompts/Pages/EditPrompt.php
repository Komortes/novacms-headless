<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use App\Models\Prompt;
use App\Services\PromptRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class EditPrompt extends EditRecord
{
    protected static string $resource = PromptResource::class;

    protected string $view = 'filament.resources.prompts.pages.edit-prompt';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Review prompt behavior, keep versioning clean, and update the active contract deliberately.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compare')
                ->label('Compare')
                ->icon(Heroicon::ArrowsRightLeft)
                ->color('gray')
                ->url(function (): string {
                    /** @var Prompt $record */
                    $record = $this->getRecord();
                    $activeVersion = Prompt::query()
                        ->where('name', $record->name)
                        ->where('is_active', true)
                        ->value('version');

                    return PromptResource::getUrl('compare', [
                        'prompt' => $record->name,
                        'left' => $activeVersion ?: $record->version,
                        'right' => $record->version,
                    ]);
                }),
            Action::make('activate')
                ->label('Activate')
                ->icon(Heroicon::Bolt)
                ->color('success')
                ->visible(fn (): bool => ! (bool) $this->getRecord()->is_active)
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Prompt $record */
                    $record = $this->getRecord();

                    app(PromptRegistry::class)->activate($record);

                    $this->record = $record->refresh();

                    Notification::make()
                        ->title('Prompt activated')
                        ->body("{$record->name} {$record->version} is now active.")
                        ->success()
                        ->send();
                }),
            DeleteAction::make()
                ->hidden(fn (): bool => (bool) $this->getRecord()->is_active),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $record = $this->getRecord();

        return [
            'record' => $record,
            'parameterCount' => count(is_array($record->parameters) ? $record->parameters : []),
            'templateLineCount' => count(preg_split('/\R/u', (string) $record->template) ?: []),
            'familyHistory' => Prompt::query()
                ->where('name', $record->name)
                ->orderByDesc('id')
                ->get(['name', 'version', 'is_active', 'updated_at', 'template', 'parameters'])
                ->map(fn (Prompt $prompt): array => [
                    'name' => (string) $prompt->name,
                    'version' => (string) $prompt->version,
                    'is_active' => (bool) $prompt->is_active,
                    'updated_at' => $prompt->updated_at?->diffForHumans() ?? 'n/a',
                    'template_lines' => count(preg_split('/\R/u', (string) $prompt->template) ?: []),
                    'parameter_count' => count(is_array($prompt->parameters) ? $prompt->parameters : []),
                    'edit_url' => static::getResource()::getUrl('edit', ['record' => $prompt]),
                    'compare_url' => static::getResource()::getUrl('compare', [
                        'prompt' => $prompt->name,
                        'left' => $record->version,
                        'right' => $prompt->version,
                    ]),
                ])
                ->take(6)
                ->all(),
        ];
    }
}
