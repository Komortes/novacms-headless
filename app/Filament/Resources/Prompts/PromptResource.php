<?php

namespace App\Filament\Resources\Prompts;

use App\Filament\Resources\Prompts\Pages\CreatePrompt;
use App\Filament\Resources\Prompts\Pages\EditPrompt;
use App\Filament\Resources\Prompts\Pages\ListPrompts;
use App\Filament\Resources\Prompts\Pages\ComparePrompts;
use App\Filament\Resources\Prompts\Schemas\PromptForm;
use App\Filament\Resources\Prompts\Tables\PromptsTable;
use App\Models\Prompt;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;

class PromptResource extends Resource
{
    protected static ?string $model = Prompt::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $navigationLabel = 'Prompts';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 4;

    protected static ?string $recordTitleAttribute = 'name';

    public static function form(Schema $schema): Schema
    {
        return PromptForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return PromptsTable::configure($table);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPrompts::route('/'),
            'compare' => ComparePrompts::route('/compare'),
            'create' => CreatePrompt::route('/create'),
            'edit' => EditPrompt::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $inactive = Prompt::query()
            ->where('is_active', false)
            ->count();

        return $inactive > 0 ? (string) $inactive : null;
    }
}
