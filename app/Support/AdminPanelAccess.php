<?php

namespace App\Support;

use App\Models\User;

class AdminPanelAccess
{
    public static function user(): ?User
    {
        $user = auth()->user();

        return $user instanceof User ? $user : null;
    }

    public static function canAccessPanel(): bool
    {
        return static::user()?->canAccessAdminPanel() ?? false;
    }

    public static function canAccessContentWorkspace(): bool
    {
        return static::user()?->canAccessContentWorkspace() ?? false;
    }

    public static function canCreateContent(): bool
    {
        return static::user()?->canCreateContent() ?? false;
    }

    public static function canUpdateContent(): bool
    {
        return static::user()?->canUpdateContent() ?? false;
    }

    public static function canDeleteContent(): bool
    {
        return static::user()?->canDeleteContent() ?? false;
    }

    public static function canChangeContentStatus(): bool
    {
        return static::user()?->canChangeContentStatus() ?? false;
    }

    public static function canQueueSummaries(): bool
    {
        return static::user()?->canQueueSummaries() ?? false;
    }

    public static function canAccessQueueOperations(): bool
    {
        return static::user()?->canAccessQueueOperations() ?? false;
    }

    public static function canManageContentCatalog(): bool
    {
        return static::user()?->canManageContentCatalog() ?? false;
    }

    public static function canManageEmbeddings(): bool
    {
        return static::user()?->canManageEmbeddings() ?? false;
    }

    public static function canManageAiSettings(): bool
    {
        return static::user()?->canManageAiSettings() ?? false;
    }

    public static function canManagePrompts(): bool
    {
        return static::user()?->canManagePrompts() ?? false;
    }

    public static function canManageApiAccess(): bool
    {
        return static::user()?->canManageApiAccess() ?? false;
    }
}
