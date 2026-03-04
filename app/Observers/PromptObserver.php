<?php

namespace App\Observers;

use App\Models\Prompt;

class PromptObserver
{
    public function saved(Prompt $prompt): void
    {
        if (! $prompt->is_active) {
            return;
        }

        Prompt::query()
            ->where('name', $prompt->name)
            ->whereKeyNot($prompt->id)
            ->update(['is_active' => false]);
    }
}
