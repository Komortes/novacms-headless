<?php

namespace App\Filament\Resources\Contents\Schemas;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Select::make('type')
                    ->options([
                        ContentType::POST->value => 'Post',
                        ContentType::PAGE->value => 'Page',
                    ])
                    ->default(ContentType::POST->value)
                    ->required(),
                Select::make('status')
                    ->options([
                        ContentStatus::DRAFT->value => 'Draft',
                        ContentStatus::PUBLISHED->value => 'Published',
                        ContentStatus::ARCHIVED->value => 'Archived',
                    ])
                    ->default(ContentStatus::DRAFT->value)
                    ->required(),
                TextInput::make('title')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                TextInput::make('slug')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                TextInput::make('locale')
                    ->required()
                    ->default('en')
                    ->maxLength(10),
                MarkdownEditor::make('body')
                    ->required()
                    ->columnSpanFull(),
            ]);
    }
}
