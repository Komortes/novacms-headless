<?php

namespace App\Filament\Widgets;

use Filament\Widgets\AccountWidget;

class AccountSummaryWidget extends AccountWidget
{
    protected static ?int $sort = -2;

    protected string $view = 'filament.widgets.account-summary-widget';
}
