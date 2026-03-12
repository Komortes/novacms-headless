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
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Validation\Rules\Unique;

class ContentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Content source')
                    ->description('Write the canonical markdown source. Saving recalculates the content hash and marks AI output as stale when the body changes.')
                    ->columnSpan(2)
                    ->columns(6)
                    ->schema([
                        Select::make('type')
                            ->options([
                                ContentType::POST->value => 'Post',
                                ContentType::PAGE->value => 'Page',
                            ])
                            ->default(ContentType::POST->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(2),
                        Select::make('status')
                            ->options([
                                ContentStatus::DRAFT->value => 'Draft',
                                ContentStatus::PUBLISHED->value => 'Published',
                                ContentStatus::ARCHIVED->value => 'Archived',
                            ])
                            ->default(ContentStatus::DRAFT->value)
                            ->required()
                            ->native(false)
                            ->columnSpan(2),
                        TextInput::make('locale')
                            ->required()
                            ->default('en')
                            ->maxLength(10)
                            ->helperText('Used together with slug for uniqueness.')
                            ->columnSpan(2),
                        TextInput::make('title')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slug')
                            ->required()
                            ->unique(
                                ignoreRecord: true,
                                modifyRuleUsing: fn (Unique $rule, Get $get): Unique => $rule
                                    ->where('locale', (string) ($get('locale') ?: 'en')),
                            )
                            ->helperText('Public identifier, unique per locale.')
                            ->maxLength(255)
                            ->columnSpan(3),
                        Placeholder::make('slug_hint')
                            ->label('Routing note')
                            ->content('Use a stable slug. Changing it later can invalidate frontend links and search references.')
                            ->columnSpan(3),
                        MarkdownEditor::make('body')
                            ->required()
                            ->helperText('Primary input for summary generation, FAQ extraction, tags, and embeddings.')
                            ->columnSpanFull()
                            ->maxLength(100000),
                    ]),
                Section::make('AI pipeline')
                    ->description('Operational metadata for the current record. Use this rail to verify readiness before leaving the form.')
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
                            ->label('Expected flow')
                            ->content('Save first, then queue generation from the header action or from the content list when the draft is stable.')
                            ->columnSpanFull(),
                        Placeholder::make('publish_hint')
                            ->label('Publishing note')
                            ->content('Published content should have a ready summary with usable TL;DR, tags, bullets, and at least one FAQ pair.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
