<?php

namespace App\Filament\Resources\Contents;

use App\Filament\Resources\Contents\Pages\ContentFaqInfo;
use App\Filament\Resources\Contents\Pages\CreateContent;
use App\Filament\Resources\Contents\Pages\EditContent;
use App\Filament\Resources\Contents\Pages\ListContents;
use App\Filament\Resources\Contents\Pages\ViewContent;
use App\Filament\Resources\Contents\Schemas\ContentForm;
use App\Filament\Resources\Contents\Schemas\ContentInfolist;
use App\Filament\Resources\Contents\Tables\ContentsTable;
use App\Filament\Widgets\ContentOverviewWidget;
use App\Models\Content;
use App\Models\ContentAiSummary;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ContentResource extends Resource
{
    protected static ?string $model = Content::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::DocumentText;

    protected static ?string $recordTitleAttribute = 'title';

    protected static ?string $navigationLabel = 'Content';

    protected static string|\UnitEnum|null $navigationGroup = 'Workspace';

    protected static ?int $navigationSort = 1;

    protected static ?string $modelLabel = 'Content';

    protected static ?string $pluralModelLabel = 'Contents';

    public static function form(Schema $schema): Schema
    {
        return ContentForm::configure($schema);
    }

    public static function table(Table $table): Table
    {
        return ContentsTable::configure($table);
    }

    public static function infolist(Schema $schema): Schema
    {
        return ContentInfolist::configure($schema);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getWidgets(): array
    {
        return [
            ContentOverviewWidget::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListContents::route('/'),
            'faq-info' => ContentFaqInfo::route('/faq-info'),
            'create' => CreateContent::route('/create'),
            'view' => ViewContent::route('/{record}'),
            'edit' => EditContent::route('/{record}/edit'),
        ];
    }

    public static function getNavigationBadge(): ?string
    {
        $nonReady = ContentAiSummary::query()
            ->whereIn('status', ['pending', 'generating', 'failed'])
            ->count();

        return $nonReady > 0 ? (string) $nonReady : null;
    }

    public static function getNavigationBadgeColor(): string|array|null
    {
        $failed = ContentAiSummary::query()
            ->where('status', 'failed')
            ->count();

        if ($failed > 0) {
            return 'danger';
        }

        return 'warning';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'AI summaries requiring attention';
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()
            ->with('summary');
    }
}
