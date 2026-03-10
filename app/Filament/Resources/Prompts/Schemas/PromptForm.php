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
            ->columns(2)
            ->components([
                Section::make('Prompt Definition')
                    ->description('Versioned prompt template used by AI pipelines.')
                    ->columnSpan(1)
                    ->columns(2)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->columnSpan(1)
                            ->helperText('Example: content.summary'),
                        TextInput::make('version')
                            ->required()
                            ->maxLength(32)
                            ->columnSpan(1)
                            ->helperText('Semver format recommended (1.0.0).'),
                        Toggle::make('is_active')
                            ->label('Active version')
                            ->helperText('Only one active version is allowed per prompt name.')
                            ->default(true)
                            ->inline(false)
                            ->columnSpanFull(),
                        Textarea::make('template')
                            ->required()
                            ->rows(18)
                            ->columnSpanFull()
                            ->helperText('Use plain text instructions. JSON output rules should be explicit.'),
                    ]),
                Section::make('Parameters')
                    ->description('Structured configuration passed into prompt rendering.')
                    ->columnSpan(1)
                    ->schema([
                        KeyValue::make('parameters')
                            ->label('Prompt parameters')
                            ->keyLabel('Key')
                            ->valueLabel('Value')
                            ->columnSpanFull()
                            ->default([]),
                    ]),
            ]);
    }
}
