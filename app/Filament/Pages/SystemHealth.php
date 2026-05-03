<?php

namespace App\Filament\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Services\RuntimeHealthService;
use App\Support\AdminPanelAccess;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Support\Enums\Width;
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

    public static function canAccess(): bool
    {
        return AdminPanelAccess::canAccessQueueOperations();
    }

    public static function shouldRegisterNavigation(): bool
    {
        return static::canAccess();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Runtime checks for Postgres/pgvector, Redis, Horizon, Reverb, and Ollama with queue risk alerts.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
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
        $data = app(RuntimeHealthService::class)->collect();
        $checks = collect((array) ($data['checks'] ?? []));
        $alertsCount = count((array) ($data['alerts'] ?? []));
        $failedCount = $checks->where('status', 'fail')->count();
        $warnCount = $checks->where('status', 'warn')->count();

        $data['statusBuckets'] = [
            [
                'label' => 'Blocking',
                'description' => 'Fix these before retrying queue work.',
                'tone' => 'rose',
                'empty' => 'No blocking failures right now.',
                'items' => $checks->where('status', 'fail')->values()->all(),
            ],
            [
                'label' => 'Degraded',
                'description' => 'Functional, but risky under load.',
                'tone' => 'amber',
                'empty' => 'No degraded services at the moment.',
                'items' => $checks->where('status', 'warn')->values()->all(),
            ],
            [
                'label' => 'Healthy',
                'description' => 'Ready for normal queue and editorial flow.',
                'tone' => 'emerald',
                'empty' => 'No healthy checks were reported.',
                'items' => $checks->where('status', 'ok')->values()->all(),
            ],
        ];
        $data['priorityActions'] = $this->priorityActions($failedCount, $warnCount, $alertsCount);
        $data['operatingLinks'] = $this->operatingLinks();

        return $data;
    }

    /**
     * @return list<array{title: string, description: string, tone: string}>
     */
    private function priorityActions(int $failedCount, int $warnCount, int $alertsCount): array
    {
        $actions = [];

        if ($failedCount > 0) {
            $actions[] = [
                'title' => 'Repair failing services first',
                'description' => 'Database, Redis, Horizon, Reverb, or Ollama failures will invalidate queue-side retries.',
                'tone' => 'rose',
            ];
        }

        if ($alertsCount > 0) {
            $actions[] = [
                'title' => 'Reduce queue pressure next',
                'description' => 'After runtime failures are cleared, inspect queue lag and failed-run growth in Queue Center.',
                'tone' => 'amber',
            ];
        }

        if ($warnCount > 0) {
            $actions[] = [
                'title' => 'Resolve degraded checks',
                'description' => 'Warnings do not always block work, but they are usually the precursor to a queue incident.',
                'tone' => 'sky',
            ];
        }

        if ($actions === []) {
            $actions[] = [
                'title' => 'Baseline looks healthy',
                'description' => 'No failures or warnings are active. Editorial and queue operations can proceed normally.',
                'tone' => 'emerald',
            ];
        }

        return $actions;
    }

    /**
     * @return list<array{label: string, description: string, href: string, tone: string}>
     */
    private function operatingLinks(): array
    {
        $links = [
            [
                'label' => 'Queue Center',
                'description' => 'Return to pending, generating, and failed runs after runtime checks are stable.',
                'href' => QueueCenter::getUrl(),
                'tone' => 'amber',
            ],
            [
                'label' => 'Content Workspace',
                'description' => 'Open records that were affected by runtime failures and decide whether to retry.',
                'href' => ContentResource::getUrl('index'),
                'tone' => 'indigo',
            ],
            AdminPanelAccess::canManageAiSettings() ? [
                'label' => 'AI Settings',
                'description' => 'Adjust provider defaults and timeouts if the runtime failure pattern points to configuration drift.',
                'href' => AiSettings::getUrl(),
                'tone' => 'emerald',
            ] : null,
        ];

        return array_values(array_filter($links));
    }
}
