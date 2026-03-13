<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use App\Filament\Widgets\PromptWorkspaceWidget;
use App\Services\PromptCatalogManager;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ListRecords;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Throwable;

class ListPrompts extends ListRecords
{
    protected static string $resource = PromptResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage prompt versions and activate the current production variant.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('compareVersions')
                ->label('Compare')
                ->icon(Heroicon::ArrowsRightLeft)
                ->color('gray')
                ->url(PromptResource::getUrl('compare')),
            Action::make('exportAll')
                ->label('Export all')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(function () {
                    $payload = app(PromptCatalogManager::class)->export();

                    return response()->streamDownload(
                        function () use ($payload): void {
                            echo json_encode(
                                $payload,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            );
                        },
                        'prompts-all-'.now()->format('Ymd-His').'.json',
                        ['Content-Type' => 'application/json'],
                    );
                }),
            Action::make('exportActive')
                ->label('Export active')
                ->icon(Heroicon::ArrowDownTray)
                ->color('gray')
                ->action(function () {
                    $payload = app(PromptCatalogManager::class)->export(activeOnly: true);

                    return response()->streamDownload(
                        function () use ($payload): void {
                            echo json_encode(
                                $payload,
                                JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                            );
                        },
                        'prompts-active-'.now()->format('Ymd-His').'.json',
                        ['Content-Type' => 'application/json'],
                    );
                }),
            Action::make('importJson')
                ->label('Import JSON')
                ->icon(Heroicon::ArrowUpTray)
                ->color('gray')
                ->modalHeading('Import prompt versions')
                ->modalSubmitActionLabel('Import')
                ->modalWidth('5xl')
                ->schema([
                    Textarea::make('payload')
                        ->label('JSON payload')
                        ->rows(18)
                        ->required()
                        ->helperText('Supports exported bundle format or one prompt object.'),
                    Toggle::make('activate_imported')
                        ->label('Apply active flags from payload')
                        ->default(true)
                        ->inline(false),
                ])
                ->action(function (array $data): void {
                    try {
                        $result = app(PromptCatalogManager::class)->importFromJson(
                            payload: (string) ($data['payload'] ?? ''),
                            activateImported: (bool) ($data['activate_imported'] ?? true),
                        );
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Import failed')
                            ->body(Str::limit($exception->getMessage(), 220))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Import completed')
                        ->body("Upserted {$result['upserted']} prompt versions.")
                        ->success()
                        ->send();
                }),
            CreateAction::make(),
        ];
    }

    protected function getHeaderWidgets(): array
    {
        return [
            PromptWorkspaceWidget::class,
        ];
    }

    public function getHeaderWidgetsColumns(): int|array
    {
        return 1;
    }
}
