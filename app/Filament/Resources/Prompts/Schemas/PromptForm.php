<?php

namespace App\Filament\Resources\Prompts\Schemas;

use App\Models\Prompt;
use Filament\Forms\Components\KeyValue;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;

class PromptForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(4)
            ->components([
                Section::make('Prompt Definition')
                    ->description('Versioned prompt contract for the async AI pipeline. Keep instructions explicit and output shape stable.')
                    ->columnSpan(3)
                    ->columns(6)
                    ->schema([
                        TextInput::make('name')
                            ->required()
                            ->maxLength(120)
                            ->live(debounce: 500)
                            ->columnSpan(3)
                            ->helperText('Example: content.summary'),
                        TextInput::make('version')
                            ->required()
                            ->maxLength(32)
                            ->live(debounce: 500)
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
                Section::make('Registry Context')
                    ->description('Keep the family slot, active version, and parameter shape visible while you edit.')
                    ->columnSpan(1)
                    ->schema([
                        Placeholder::make('registry_slot')
                            ->label('Registry slot')
                            ->content(function (Get $get): string {
                                $name = trim((string) ($get('name') ?: 'prompt.family'));
                                $version = trim((string) ($get('version') ?: 'new-version'));

                                return $name.' · '.$version;
                            }),
                        Placeholder::make('active_family_version')
                            ->label('Active version in family')
                            ->content(function (Get $get): string {
                                $name = trim((string) ($get('name') ?: ''));

                                if ($name === '') {
                                    return 'Set prompt name to resolve active family version.';
                                }

                                $active = Prompt::query()
                                    ->where('name', $name)
                                    ->where('is_active', true)
                                    ->latest('id')
                                    ->first();

                                return $active ? (string) $active->version : 'No active version yet.';
                            }),
                        Placeholder::make('parameter_count')
                            ->label('Parameter keys')
                            ->content(function (Get $get): string {
                                $parameters = $get('parameters');

                                return (string) count(is_array($parameters) ? $parameters : []);
                            }),
                        Placeholder::make('editing_rule')
                            ->label('Release rule')
                            ->content('Treat activation as a release. Compare versions before promoting a candidate to active.'),
                    ]),
                Section::make('Parameters')
                    ->description('Structured values passed into prompt rendering. Stable keys make versions easier to compare.')
                    ->columnSpan(4)
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
