<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;
use Illuminate\Contracts\Support\Htmlable;

class ListPrompts extends ListRecords
{
    protected static string $resource = PromptResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Manage prompt versions and activate the current production variant.';
    }

    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make(),
        ];
    }
}
