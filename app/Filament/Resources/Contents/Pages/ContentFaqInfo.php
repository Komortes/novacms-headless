<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use Filament\Resources\Pages\Page;
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
}

