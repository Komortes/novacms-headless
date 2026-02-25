<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Pages\AiSettings;
use App\Filament\Resources\Contents\ContentResource;
use Filament\Actions\Action;
use Filament\Resources\Pages\Page;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;

class ContentFaqInfo extends Page
{
    protected static string $resource = ContentResource::class;

    protected static ?string $title = 'FAQ & AI Flow';

    protected static ?string $breadcrumb = 'FAQ & Info';

    protected string $view = 'filament.resources.contents.pages.content-faq-info';

    public function getSubheading(): string|Htmlable|null
    {
        return 'How summaries and FAQ are generated, what statuses mean, and how to operate the flow.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('aiSettings')
                ->label('AI settings')
                ->icon(Heroicon::Cog6Tooth)
                ->color('gray')
                ->url(AiSettings::getUrl()),
        ];
    }
}
