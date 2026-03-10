<?php

namespace App\Services;

use App\Models\Prompt;
use Illuminate\Support\Collection;
use InvalidArgumentException;

class PromptCatalogManager
{
    public function __construct(
        private readonly PromptRegistry $promptRegistry,
    ) {}

    /**
     * @return array{
     *     exported_at: string,
     *     count: int,
     *     prompts: list<array{name: string, version: string, template: string, parameters: array<string, mixed>, is_active: bool}>
     * }
     */
    public function export(?string $name = null, ?string $version = null, bool $activeOnly = false): array
    {
        $query = Prompt::query()->orderBy('name')->orderBy('version');

        if (is_string($name) && trim($name) !== '') {
            $query->where('name', trim($name));
        }

        if (is_string($version) && trim($version) !== '') {
            $query->where('version', trim($version));
        }

        if ($activeOnly) {
            $query->where('is_active', true);
        }

        $items = $query->get()->map(fn (Prompt $prompt): array => [
            'name' => (string) $prompt->name,
            'version' => (string) $prompt->version,
            'template' => (string) $prompt->template,
            'parameters' => is_array($prompt->parameters) ? $prompt->parameters : [],
            'is_active' => (bool) $prompt->is_active,
        ])->values()->all();

        return [
            'exported_at' => now()->toIso8601String(),
            'count' => count($items),
            'prompts' => $items,
        ];
    }

    /**
     * @return array{upserted: int, activated: int, names: list<string>}
     */
    public function importFromJson(string $payload, bool $activateImported = true): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }

        $items = $this->normalizeImportItems($decoded);

        if ($items->isEmpty()) {
            throw new InvalidArgumentException('No prompt records found in payload.');
        }

        $upserted = 0;
        $activated = 0;
        $names = [];

        foreach ($items as $item) {
            $isActive = $activateImported && (($item['is_active'] ?? false) === true);

            $this->promptRegistry->upsert(
                name: $item['name'],
                version: $item['version'],
                template: $item['template'],
                parameters: $item['parameters'],
                isActive: $isActive,
            );

            $upserted++;

            if ($isActive) {
                $activated++;
            }

            if (! in_array($item['name'], $names, true)) {
                $names[] = $item['name'];
            }
        }

        return [
            'upserted' => $upserted,
            'activated' => $activated,
            'names' => $names,
        ];
    }

    /**
     * @param  array<int|string, mixed>  $decoded
     * @return Collection<int, array{name: string, version: string, template: string, parameters: array<string, mixed>, is_active: bool}>
     */
    private function normalizeImportItems(array $decoded): Collection
    {
        if (array_key_exists('prompts', $decoded) && is_array($decoded['prompts'])) {
            $rawItems = $decoded['prompts'];
        } elseif ($this->isPromptItem($decoded)) {
            $rawItems = [$decoded];
        } else {
            $rawItems = $decoded;
        }

        if (! is_array($rawItems)) {
            return collect();
        }

        return collect($rawItems)
            ->filter(fn (mixed $item): bool => is_array($item) && $this->isPromptItem($item))
            ->map(function (array $item): array {
                $parameters = $item['parameters'] ?? [];

                return [
                    'name' => trim((string) $item['name']),
                    'version' => trim((string) $item['version']),
                    'template' => (string) $item['template'],
                    'parameters' => is_array($parameters) ? $parameters : [],
                    'is_active' => (bool) ($item['is_active'] ?? false),
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function isPromptItem(array $item): bool
    {
        return isset($item['name'], $item['version'], $item['template'])
            && is_scalar($item['name'])
            && is_scalar($item['version'])
            && is_scalar($item['template']);
    }
}
