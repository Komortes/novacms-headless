<?php

namespace App\Filament\Resources\Prompts\Schemas;

use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PromptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(3)
            ->components([
                Section::make('Prompt Definition')
                    ->description('Versioned prompt contract for the async AI pipeline. Keep instructions explicit and output shape stable.')
                    ->columnSpan(2)
                    ->columns(6)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(3)
                            ->helperText('Example: content.summary'),
                        TextInput::make('version')
                            ->required()
                            ->maxLength(32)
                            ->columnSpan(3)
                            ->helperText('Semver is recommended, for example `1.0.0`.'),
                        Toggle::make('is_active')
                            ->label('Active version')
                            ->helperText('Only one active version is allowed for each prompt name.')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('release_note')
                            ->label('Change intent')
                            ->dehydrated(false)
                            ->placeholder('Example: tighter FAQ rules, shorter TL;DR, more explicit JSON contract')
                            ->helperText('Not saved. Use it as a temporary release note while editing.')
                            ->columnSpan(4),
                        Textarea::make('template')
                            ->required()
                            ->rows(24)
                            ->columnSpanFull()
                            ->helperText('Use plain text instructions and make structured output rules explicit.'),
                    ]),
                Section::make('Parameters')
                    ->description('Structured values passed into prompt rendering. Stable keys make versions easier to compare.')
                    ->columnSpan(1)
                    ->schema([
                        KeyValue::make('parameters')
                            ->label('Prompt parameters')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->default([])
                            ->helperText('Examples: `chunk_size`, `output_language`, `max_bullets`.'),
                    ]),
            ]);
    }
}
