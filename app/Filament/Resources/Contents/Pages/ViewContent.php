<?php

namespace App\Filament\Resources\Contents\Pages;

use App\Filament\Resources\Contents\ContentResource;
use App\Models\Content;
use App\Services\ContentSummaryGenerator;
use Filament\Actions\Action;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Icons\Heroicon;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Str;
use Throwable;

class ViewContent extends ViewRecord
{
    protected static string $resource = ContentResource::class;

    public function getSubheading(): string|Htmlable|null
    {
        return 'Review generated AI output and run regeneration when content changes.';
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('faqInfo')
                ->label('FAQ & info')
                ->icon(Heroicon::QuestionMarkCircle)
                ->color('gray')
                ->url($this->getResourceUrl('faq-info')),
            Action::make('generateSummary')
                ->label('Generate summary')
                ->icon(Heroicon::ArrowPath)
                ->color('info')
                ->requiresConfirmation()
                ->action(function (): void {
                    /** @var Content $record */
                    $record = $this->getRecord();

                    try {
                        app(ContentSummaryGenerator::class)->generateForContent($record);
                    } catch (Throwable $exception) {
                        Notification::make()
                            ->title('Summary generation failed')
                            ->body(Str::limit($exception->getMessage(), 200))
                            ->danger()
                            ->send();

                        return;
                    }

                    Notification::make()
                        ->title('Summary generated')
                        ->success()
                        ->send();
                }),
            EditAction::make(),
        ];
    }
}
