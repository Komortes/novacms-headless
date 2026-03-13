<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CreatePrompt extends CreateRecord
{
    protected static string $resource = PromptResource::class;

    protected string $view = 'filament.resources.prompts.pages.create-prompt';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Author a new prompt version with an explicit output contract before making it active.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'editorChecklist' => [
                'Keep JSON requirements explicit.',
                'Version changes should be intentional and comparable.',
                'Only activate when the new contract is safe for existing jobs.',
            ],
        ];
    }
}
