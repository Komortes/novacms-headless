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
                    ->description('Define the versioned prompt contract used by async AI pipelines. Keep instructions explicit and output shape stable.')
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
                            ->helperText('Semver format recommended (1.0.0).'),
                        Toggle::make('is_active')
                            ->label('Active version')
                            ->helperText('Only one active version is allowed per prompt name.')
                            ->default(true)
                            ->inline(false)
                            ->columnSpan(2),
                        TextInput::make('release_note')
                            ->label('Change intent')
                            ->dehydrated(false)
                            ->placeholder('Example: tighter FAQ rules, shorter TL;DR, more explicit JSON contract')
                            ->helperText('Not saved. This is a working note for the editor while preparing the prompt.')
                            ->columnSpan(4),
                        Textarea::make('template')
                            ->required()
                            ->rows(24)
                            ->columnSpanFull()
                            ->helperText('Use plain text instructions. JSON output rules should be explicit.'),
                    ]),
                Section::make('Parameters')
                    ->description('Structured values passed into prompt rendering. Keep keys stable so prompt versions are easy to compare.')
                    ->columnSpan(1)
                    ->schema([
                        KeyValue::make('parameters')
                            ->label('Prompt parameters')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->default([])
                            ->helperText('Examples: chunk_size, output_language, max_bullets'),
                    ]),
            ]);
    }
}
