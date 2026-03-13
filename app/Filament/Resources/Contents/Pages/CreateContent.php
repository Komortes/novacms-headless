<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Contracts\Support\Htmlable;

class CreateContent extends CreateRecord
{
    protected static string $resource = ContentResource::class;

    protected string $view = 'filament.resources.contents.pages.create-content';

    public function getSubheading(): string|Htmlable|null
    {
        return 'Create a new post or page, then move it into the async AI pipeline when the draft is stable.';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        return [
            'recommendedFlow' => [
                'Write the canonical markdown body first.',
                'Save the draft before queueing AI generation.',
                'Use balanced preset first, then review TL;DR, bullets, tags, and FAQ.',
            ],
        ];
    }
}
