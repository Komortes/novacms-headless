<?php

namespace App\Policies;

use App\Models\Prompt;
use App\Models\User;

class PromptPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->canManagePrompts();
    }

    public function view(User $user, Prompt $prompt): bool
    {
        return $user->canManagePrompts();
    }

    public function create(User $user): bool
    {
        return $user->canManagePrompts();
    }

    public function update(User $user, Prompt $prompt): bool
    {
        return $user->canManagePrompts();
    }

    public function delete(User $user, Prompt $prompt): bool
    {
        return $user->canManagePrompts();
    }

    public function deleteAny(User $user): bool
    {
        return $user->canManagePrompts();
    }
}
