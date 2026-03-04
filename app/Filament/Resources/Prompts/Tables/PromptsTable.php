<?php

namespace App\Filament\Resources\Prompts\Tables;

use App\Models\Prompt;
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
                    ->weight('semibold'),
                TextColumn::make('version')
                    ->searchable()
                    ->sortable()
                    ->badge()
                    ->color('gray'),
                IconColumn::make('is_active')
                    ->label('Active')
                    ->boolean()
                    ->sortable(),
                TextColumn::make('template')
                    ->label('Template')
                    ->limit(80)
                    ->tooltip(fn (Prompt $record): string => Str::limit((string) $record->template, 500))
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('updated_at')
                    ->label('Updated')
                    ->since()
                    ->sortable(),
            ])
            ->filters([
                TernaryFilter::make('is_active')
                    ->label('Active only'),
            ])
            ->recordActions([
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
