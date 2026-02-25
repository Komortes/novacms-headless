<?php

namespace App\Filament\Resources\Contents\Schemas;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class ContentInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Overview')
                    ->description('Content metadata and current publication state.')
                    ->columns(4)
                    ->components([
                        TextEntry::make('title')->columnSpanFull(),
                        TextEntry::make('slug')
                            ->copyable(),
                        TextEntry::make('locale'),
                        TextEntry::make('type')
                            ->badge()
                            ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state)
                            ->color(fn (mixed $state): string => match ($state->value ?? (string) $state) {
                                ContentType::POST->value => 'info',
                                ContentType::PAGE->value => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('status')
                            ->badge()
                            ->formatStateUsing(fn (mixed $state): string => $state->value ?? (string) $state)
                            ->color(fn (mixed $state): string => match ($state->value ?? (string) $state) {
                                ContentStatus::PUBLISHED->value => 'success',
                                ContentStatus::DRAFT->value => 'warning',
                                ContentStatus::ARCHIVED->value => 'gray',
                                default => 'gray',
                            }),
                        TextEntry::make('created_at')
                            ->dateTime(),
                        TextEntry::make('updated_at')
                            ->since(),
                        TextEntry::make('content_hash')
                            ->label('Content hash')
                            ->copyable()
                            ->columnSpanFull(),
                    ]),
                Section::make('AI Summary')
                    ->description('Generated asynchronously from prompt templates and current content hash.')
                    ->columns(2)
                    ->components([
                        TextEntry::make('summary.status')
                            ->label('Summary status')
                            ->badge()
                            ->state(fn ($record): string => $record->summary?->status?->value ?? SummaryStatus::PENDING->value)
                            ->color(fn (string $state): string => match ($state) {
                                SummaryStatus::READY->value => 'success',
                                SummaryStatus::GENERATING->value => 'info',
                                SummaryStatus::FAILED->value => 'danger',
                                SummaryStatus::PENDING->value => 'warning',
                                default => 'gray',
                            }),
                        TextEntry::make('summary.model')
                            ->placeholder('n/a'),
                        TextEntry::make('summary.prompt_version')
                            ->label('Prompt version')
                            ->placeholder('n/a'),
                        TextEntry::make('summary.tokens_in')
                            ->label('Tokens in')
                            ->placeholder('n/a'),
                        TextEntry::make('summary.tokens_out')
                            ->label('Tokens out')
                            ->placeholder('n/a'),
                        TextEntry::make('summary.summary_tldr')
                            ->label('TL;DR')
                            ->lineClamp(4)
                            ->placeholder('No summary generated yet.')
                            ->columnSpanFull(),
                        TextEntry::make('summary.summary_bullets')
                            ->label('Bullets')
                            ->bulleted()
                            ->listWithLineBreaks()
                            ->limitList(6)
                            ->expandableLimitedList()
                            ->placeholder('No bullets generated yet.')
                            ->columnSpanFull(),
                        TextEntry::make('summary.summary_meta_description')
                            ->label('Meta description')
                            ->lineClamp(3)
                            ->placeholder('No meta description generated yet.')
                            ->columnSpanFull(),
                        TextEntry::make('summary.summary_tags')
                            ->label('Tags')
                            ->badge()
                            ->placeholder('No tags generated yet.')
                            ->columnSpanFull(),
                        TextEntry::make('summary.last_error')
                            ->label('Last error')
                            ->placeholder('No errors')
                            ->lineClamp(4)
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),
                Section::make('FAQ')
                    ->description('Generated Q&A extracted from the content.')
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        RepeatableEntry::make('summary.summary_faq')
                            ->label(' ')
                            ->placeholder('No FAQ generated yet.')
                            ->table([
                                TableColumn::make('Question')
                                    ->width('35%'),
                                TableColumn::make('Answer')
                                    ->width('65%'),
                            ])
                            ->schema([
                                TextEntry::make('question')
                                    ->label('Question')
                                    ->lineClamp(3),
                                TextEntry::make('answer')
                                    ->label('Answer')
                                    ->lineClamp(5),
                            ]),
                    ]),
                Section::make('Source Markdown')
                    ->description('Original source used for summary generation.')
                    ->collapsible()
                    ->collapsed()
                    ->components([
                        TextEntry::make('body')
                            ->markdown()
                            ->prose()
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
