<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class PromptRegistry
{
    public function findActive(string $name, ?string $version = null): ?Prompt
    {
        $query = Prompt::query()
            ->named($name)
            ->active();

        if ($version !== null) {
            $query->version($version);
        }

        return $query->latest('id')->first();
    }

    public function resolveActive(string $name, ?string $version = null): Prompt
    {
        $prompt = $this->findActive($name, $version);

        if ($prompt !== null) {
            return $prompt;
        }

        $parts = [$name];

        if ($version !== null) {
            $parts[] = $version;
        }

        throw (new ModelNotFoundException)->setModel(Prompt::class, $parts);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    public function upsert(
        string $name,
        string $version,
        string $template,
        array $parameters = [],
        bool $isActive = true,
    ): Prompt {
        return DB::transaction(function () use ($name, $version, $template, $parameters, $isActive): Prompt {
            $prompt = Prompt::query()->updateOrCreate(
                [
                    'name' => $name,
                    'version' => $version,
                ],
                [
                    'template' => $template,
                    'parameters' => $parameters,
                    'is_active' => $isActive,
                ],
            );

            if ($isActive) {
                Prompt::query()
                    ->named($name)
                    ->whereKeyNot($prompt->id)
                    ->update(['is_active' => false]);
            }

            return $prompt->refresh();
        });
    }

    public function activate(Prompt $prompt): Prompt
    {
        return DB::transaction(function () use ($prompt): Prompt {
            Prompt::query()
                ->named($prompt->name)
                ->whereKeyNot($prompt->id)
                ->update(['is_active' => false]);

            $prompt->forceFill(['is_active' => true])->save();

            return $prompt->refresh();
        });
    }
}
