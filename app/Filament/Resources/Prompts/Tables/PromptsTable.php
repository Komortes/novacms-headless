<?php

namespace App\Filament\Resources\Prompts\Tables;

use App\Filament\Resources\Prompts\PromptResource;
use App\Models\Prompt;
use App\Services\PromptCatalogManager;
use App\Services\PromptRegistry;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class PromptsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->defaultSort('updated_at', 'desc')
            ->searchPlaceholder('Search prompt name or version')
            ->paginated([10, 25, 50])
            ->columns([
                TextColumn::make('name')
                    ->searchable()
                    ->sortable()
                    ->weight('semibold')
                    ->description(function (Prompt $record): string {
                        $familyVersions = Prompt::query()
                            ->where('name', $record->name)
                            ->count();
                        $activeVersion = Prompt::query()
                            ->where('name', $record->name)
                            ->where('is_active', true)
                            ->value('version');

                        return sprintf(
                            '%d versions in family%s',
                            $familyVersions,
                            $activeVersion ? ' · active '.$activeVersion : '',
                        );
                    }),
                TextColumn::make('version')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color(fn (Prompt $record): string => $record->is_active ? 'success' : 'gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('parameter_count')
                    ->label('Params')
                    ->state(fn (Prompt $record): string => (string) count(is_array($record->parameters) ? $record->parameters : []))
                    ->badge()
                    ->color('sky'),
                TextColumn::make('template_lines')
                    ->label('Template')
                    ->state(fn (Prompt $record): string => count(preg_split('/\R/u', (string) $record->template) ?: []).' lines')
                    ->description(fn (Prompt $record): string => Str::limit(trim((string) $record->template), 72))
                    ->wrap(),
                TextColumn::make('template')
                    ->label('Template')
                    ->limit(80)
                    ->tooltip(fn (Prompt $record): string => Str::limit((string) $record->template, 500))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable()
                    ->description(fn (Prompt $record): string => $record->updated_at?->format('M j, Y H:i') ?? 'n/a'),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active only'),
            ])
            ->recordActions([
                Action::make('compare')
                    ->label('Compare')
                    ->icon(Heroicon::ArrowsRightLeft)
                    ->color('gray')
                    ->url(fn (Prompt $record): string => PromptResource::getUrl('compare', [
                        'prompt' => $record->name,
                        'left' => $record->version,
                    ])),
                Action::make('export')
                    ->label('Export')
                    ->icon(Heroicon::ArrowDownTray)
                    ->color('gray')
                    ->action(function (Prompt $record) {
                        $payload = app(PromptCatalogManager::class)->export(
                            name: $record->name,
                            version: $record->version,
                        );

                        return response()->streamDownload(
                            function () use ($payload): void {
                                echo json_encode(
                                    $payload,
                                    JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
                                );
                            },
                            sprintf(
                                'prompt-%s-%s.json',
                                Str::slug($record->name),
                                Str::slug($record->version),
                            ),
                            ['Content-Type' => 'application/json'],
                        );
                    }),
                Action::make('activate')
                    ->label('Activate')
                    ->icon(Heroicon::Bolt)
                    ->color('success')
                    ->visible(fn (Prompt $record): bool => ! $record->is_active)
                    ->requiresConfirmation()
                    ->action(function (Prompt $record): void {
                        app(PromptRegistry::class)->activate($record);

                        Notification::make()
                            ->title('Prompt activated')
                            ->body("{$record->name} {$record->version} is now active.")
                            ->success()
                            ->send();
                    }),
                EditAction::make(),
                DeleteAction::make()
                    ->hidden(fn (Prompt $record): bool => $record->is_active),
            ])
            ->emptyStateIcon(Heroicon::DocumentText)
            ->emptyStateHeading('No prompt versions')
            ->emptyStateDescription('Create prompt templates and mark the current active version.');
    }
}
