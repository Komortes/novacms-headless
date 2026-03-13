<?php

namespace App\Policies;

use App\Models\Content;
use App\Models\User;

class ContentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canAccessContentWorkspace();
    }

    public function view(User $user, Content $content): bool
    {
        return $user->canAccessContentWorkspace();
    }

    public function create(User $user): bool
    {
        return $user->canCreateContent();
    }

    public function update(User $user, Content $content): bool
    {
        return $user->canUpdateContent();
    }

    public function delete(User $user, Content $content): bool
    {
        return $user->canDeleteContent();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canDeleteContent();
    }
}
