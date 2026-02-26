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
            ->columns(2)
            ->components([
                Section::make('Overview')
                    ->description('Content metadata and current publication state.')
                    ->columns(4)
                    ->columnSpan(1)
                    ->extraAttributes(['class' => 'h-full'])
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
                    ->description('Generation status, provider metadata and diagnostics.')
                    ->columns(2)
                    ->columnSpan(1)
                    ->extraAttributes(['class' => 'h-full'])
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
                        TextEntry::make('summary.generation_ms')
                            ->label('Latency')
                            ->formatStateUsing(fn (mixed $state): string => is_numeric($state) ? ((int) $state).' ms' : 'n/a')
                            ->placeholder('n/a'),
                        TextEntry::make('summary.last_error')
                            ->label('Last error')
                            ->placeholder('No errors')
                            ->lineClamp(3)
                            ->color('danger')
                            ->columnSpanFull(),
                    ]),
                Section::make('Generated Output')
                    ->description('Structured fields produced by the active prompt and selected model.')
                    ->columns(2)
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('summary.summary_tldr')
                            ->label('TL;DR')
                            ->lineClamp(5)
                            ->placeholder('No summary generated yet.')
                            ->columnSpanFull(),
                        TextEntry::make('summary.summary_bullets')
                            ->label('Bullets')
                            ->bulleted()
                            ->listWithLineBreaks()
                            ->limitList(8)
                            ->expandableLimitedList()
                            ->placeholder('No bullets generated yet.')
                            ->columnSpan(1),
                        TextEntry::make('summary.summary_meta_description')
                            ->label('Meta description')
                            ->lineClamp(5)
                            ->placeholder('No meta description generated yet.')
                            ->columnSpan(1),
                        TextEntry::make('summary.summary_tags')
                            ->label('Tags')
                            ->badge()
                            ->placeholder('No tags generated yet.')
                            ->columnSpanFull(),
                    ]),
                Section::make('FAQ')
                    ->description('Generated Q&A extracted from the content.')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
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
                Section::make('Run Timeline')
                    ->description('Latest queue and generation events for this content.')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->components([
                        RepeatableEntry::make('summary_events_timeline')
                            ->label(' ')
                            ->state(fn ($record): array => $record
                                ->summaryEvents()
                                ->latest('id')
                                ->limit(12)
                                ->get()
                                ->map(fn ($event): array => [
                                    'event' => (string) $event->event,
                                    'created_at' => $event->created_at,
                                    'provider' => (string) ($event->provider ?? 'n/a'),
                                    'model' => (string) ($event->model ?? 'n/a'),
                                    'wait_ms' => is_numeric($event->wait_ms) ? ((int) $event->wait_ms).' ms' : 'n/a',
                                    'duration_ms' => is_numeric($event->duration_ms) ? ((int) $event->duration_ms).' ms' : 'n/a',
                                    'message' => (string) ($event->message ?? ''),
                                ])
                                ->all())
                            ->placeholder('No timeline events yet.')
                            ->schema([
                                TextEntry::make('event')
                                    ->badge()
                                    ->color(fn (string $state): string => match ($state) {
                                        'completed' => 'success',
                                        'failed' => 'danger',
                                        'started' => 'info',
                                        'queued' => 'warning',
                                        'cancelled' => 'gray',
                                        'skipped' => 'gray',
                                        default => 'gray',
                                    }),
                                TextEntry::make('created_at')
                                    ->label('When')
                                    ->since(),
                                TextEntry::make('provider'),
                                TextEntry::make('model')
                                    ->limit(24),
                                TextEntry::make('wait_ms')
                                    ->label('Queue wait'),
                                TextEntry::make('duration_ms')
                                    ->label('Run time'),
                                TextEntry::make('message')
                                    ->placeholder(' ')
                                    ->lineClamp(2)
                                    ->columnSpanFull(),
                            ]),
                    ]),
                Section::make('Source Markdown')
                    ->description('Original source used for summary generation.')
                    ->collapsible()
                    ->collapsed()
                    ->columnSpanFull()
                    ->components([
                        TextEntry::make('body')
                            ->markdown()
                            ->extraAttributes(['class' => 'max-w-none'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }
}
