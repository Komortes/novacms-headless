<?php

namespace App\Filament\Pages;

use App\Services\RuntimeHealthService;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class SystemHealth extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::Signal;

    protected static ?string $navigationLabel = 'System Health';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 4;

    protected static ?string $title = 'System Health';

    protected static ?string $slug = 'system-health';

    protected string $view = 'filament.pages.system-health';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Runtime checks for Postgres/pgvector, Redis, Horizon, Reverb, and Ollama with queue risk alerts.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('refresh')
                ->label('Run checks')
                ->icon(Heroicon::ArrowPath)
                ->color('gray')
                ->action(fn (): null => null),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $alertsCount = count(app(RuntimeHealthService::class)->queueAlerts());

        return $alertsCount > 0 ? (string) $alertsCount : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        return count(app(RuntimeHealthService::class)->queueAlerts()) > 0 ? 'danger' : 'success';
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return app(RuntimeHealthService::class)->collect();
    }
}
