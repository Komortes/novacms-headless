<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;
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
        ];
    }
}
