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
        $versionCounts = Prompt::query()
            ->selectRaw('name, count(*) as aggregate')
            ->groupBy('name')
            ->pluck('aggregate', 'name');

        $featuredFamilies = Prompt::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get(['name', 'version', 'updated_at'])
            ->map(fn (Prompt $prompt): array => [
                'name' => (string) $prompt->name,
                'active_version' => (string) $prompt->version,
                'versions' => (int) ($versionCounts[$prompt->name] ?? 1),
                'updated_at' => $prompt->updated_at?->diffForHumans() ?? 'n/a',
            ])
            ->take(4)
            ->all();

        $recentChanges = Prompt::query()
            ->latest('updated_at')
            ->take(4)
            ->get(['name', 'version', 'is_active', 'updated_at'])
            ->map(fn (Prompt $prompt): array => [
                'name' => (string) $prompt->name,
                'version' => (string) $prompt->version,
                'is_active' => (bool) $prompt->is_active,
                'updated_at' => $prompt->updated_at?->diffForHumans() ?? 'n/a',
            ])
            ->all();

        return [
            'totalPrompts' => Prompt::query()->count(),
            'activePrompts' => Prompt::query()->where('is_active', true)->count(),
            'families' => Prompt::query()->distinct('name')->count('name'),
            'inactivePrompts' => Prompt::query()->where('is_active', false)->count(),
            'featuredFamilies' => $featuredFamilies,
            'recentChanges' => $recentChanges,
        ];
    }
}
