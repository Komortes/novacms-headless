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
            ->columns(4)
            ->components([
                Section::make('Content source')
                    ->description('Canonical markdown source. Saving refreshes the content hash and marks stale AI output for regeneration.')
                    ->columnSpan(3)
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
                            ->content('Use a stable slug. Late changes create friction for frontend routes and search references.')
                            ->columnSpan(3),
                        MarkdownEditor::make('body')
                            ->required()
                            ->placeholder("# Start with the canonical draft\n\nUse headings, short sections, and explicit structure.")
                            ->helperText('Canonical source for summary generation, FAQ extraction, tags, and embeddings.')
                            ->columnSpanFull()
                            ->maxLength(100000),
                    ]),
                Section::make('AI pipeline')
                    ->description('Current AI state, source identity, and quick review signals for this record.')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('summary_status')
                            ->label('Summary')
                            ->content(fn (?Content $record): string => $record?->summary?->status?->value ?? 'pending'),
                        Placeholder::make('summary_model')
                            ->label('Model')
                            ->content(fn (?Content $record): string => $record?->summary?->model ?? 'n/a'),
                        Placeholder::make('summary_prompt_version')
                            ->label('Prompt')
                            ->content(fn (?Content $record): string => $record?->summary?->prompt_version ?? 'n/a'),
                        Placeholder::make('summary_shape')
                            ->label('Structured output')
                            ->content(function (?Content $record): string {
                                $summary = $record?->summary;

                                return sprintf(
                                    '%d bullets · %d FAQ pairs · %d tags',
                                    count($summary?->summary_bullets ?? []),
                                    count($summary?->summary_faq ?? []),
                                    count($summary?->summary_tags ?? []),
                                );
                            }),
                        Placeholder::make('summary_runtime')
                            ->label('Latest runtime')
                            ->content(fn (?Content $record): string => is_numeric($record?->summary?->generation_ms)
                                ? ((int) $record->summary->generation_ms).' ms · in '.($record->summary->tokens_in ?? 'n/a').' / out '.($record->summary->tokens_out ?? 'n/a')
                                : 'n/a'),
                        Placeholder::make('content_hash')
                            ->label('Content hash')
                            ->content(fn (?Content $record): string => $record?->content_hash ?? 'Will be generated on save')
                            ->columnSpanFull(),
                        Placeholder::make('publish_hint')
                            ->label('Review gate')
                            ->content('Publish only after the summary is ready and the TL;DR, bullets, tags, and FAQ are worth keeping.')
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
