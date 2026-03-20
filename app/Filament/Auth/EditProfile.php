<?php

namespace App\Filament\Auth;

use Filament\Auth\Pages\EditProfile as BaseEditProfile;
use Filament\Support\Enums\Width;

class EditProfile extends BaseEditProfile
{
    protected string $view = 'filament.auth.edit-profile';

    public static function getLabel(): string
    {
        return 'Account';
    }

    public function getMaxContentWidth(): Width|string|null
    {
        return Width::Full;
    }
}
