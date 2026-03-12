<?php

namespace App\Filament\Widgets;

use App\Models\Prompt;
use Filament\Widgets\Widget;

class PromptWorkspaceWidget extends Widget
{
    protected string $view = 'filament.widgets.prompt-workspace-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = -2;

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'totalPrompts' => Prompt::query()->count(),
            'activePrompts' => Prompt::query()->where('is_active', true)->count(),
            'families' => Prompt::query()->distinct('name')->count('name'),
            'inactivePrompts' => Prompt::query()->where('is_active', false)->count(),
        ];
    }
}
