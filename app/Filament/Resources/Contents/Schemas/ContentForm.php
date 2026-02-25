<?php

namespace App\Filament\Resources\Contents\Schemas;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use Filament\Forms\Components\MarkdownEditor;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(2)
            ->components([
                Section::make('Content source')
                    ->description('Edit source markdown. Saving updates content hash and marks AI summary as pending.')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
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
                            ->helperText('Unique per locale.')
                            ->maxLength(255),
                        TextInput::make('locale')
                            ->required()
                            ->default('en')
                            ->maxLength(10),
                        MarkdownEditor::make('body')
                            ->required()
                            ->helperText('Main source for summary generation.')
                            ->columnSpanFull(),
                    ]),
                Section::make('AI pipeline')
                    ->description('Status and metadata used by async summary generation.')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('summary_status')
                            ->label('Current summary status')
                            ->content(fn (?Content $record): string => $record?->summary?->status?->value ?? 'pending'),
                        Placeholder::make('summary_model')
                            ->label('Last model')
                            ->content(fn (?Content $record): string => $record?->summary?->model ?? 'n/a'),
                        Placeholder::make('summary_prompt_version')
                            ->label('Last prompt version')
                            ->content(fn (?Content $record): string => $record?->summary?->prompt_version ?? 'n/a'),
                        Placeholder::make('content_hash')
                            ->label('Content hash')
                            ->content(fn (?Content $record): string => $record?->content_hash ?? 'Will be generated on save')
                            ->columnSpanFull(),
                        Placeholder::make('summary_hint')
                            ->label('Workflow')
                            ->content('After saving content, use "Generate summary" from table/view pages.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
