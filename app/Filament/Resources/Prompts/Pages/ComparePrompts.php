<?php

namespace App\Filament\Resources\Prompts\Pages;

use App\Filament\Resources\Prompts\PromptResource;
use App\Models\Prompt;
use Filament\Resources\Pages\Page;
use Illuminate\Contracts\Support\Htmlable;

class ComparePrompts extends Page
{
    protected static string $resource = PromptResource::class;

    protected static ?string $title = 'Compare Prompt Versions';

    protected static ?string $breadcrumb = 'Compare';

    protected string $view = 'filament.resources.prompts.pages.compare-prompts';

    public ?string $promptName = null;

    public ?string $leftVersion = null;

    public ?string $rightVersion = null;

    /**
     * @var array<int, string>
     */
    public array $promptNames = [];

    /**
     * @var array<int, string>
     */
    public array $versionOptions = [];

    /**
     * @var array<string, mixed>|null
     */
    public ?array $leftPrompt = null;

    /**
     * @var array<string, mixed>|null
     */
    public ?array $rightPrompt = null;

    /**
     * @var array{added: list<string>, removed: list<string>, changed: list<string>, unchanged: list<string>}
     */
    public array $parameterDiff = [
        'added' => [],
        'removed' => [],
        'changed' => [],
        'unchanged' => [],
    ];

    /**
     * @var array{left_lines: int, right_lines: int, added_preview: list<string>, removed_preview: list<string>}
     */
    public array $templateDiff = [
        'left_lines' => 0,
        'right_lines' => 0,
        'added_preview' => [],
        'removed_preview' => [],
    ];

    public function mount(): void
    {
        $this->promptNames = Prompt::query()
            ->select('name')
            ->distinct()
            ->orderBy('name')
            ->pluck('name')
            ->all();

        $requestedName = request()->query('prompt');
        $requestedLeft = request()->query('left');
        $requestedRight = request()->query('right');

        if (is_string($requestedName) && in_array($requestedName, $this->promptNames, true)) {
            $this->promptName = $requestedName;
        } else {
            $this->promptName = $this->promptNames[0] ?? null;
        }

        $this->versionOptions = $this->resolveVersionOptions();

        if (is_string($requestedLeft) && in_array($requestedLeft, $this->versionOptions, true)) {
            $this->leftVersion = $requestedLeft;
        }

        if (is_string($requestedRight) && in_array($requestedRight, $this->versionOptions, true)) {
            $this->rightVersion = $requestedRight;
        }

        $this->applyDefaultVersions();
        $this->compare();
    }

    public function getSubheading(): string|Htmlable|null
    {
        return 'Inspect template and parameter changes between prompt versions before activation.';
    }

    public function updatedPromptName(): void
    {
        $this->versionOptions = $this->resolveVersionOptions();
        $this->leftVersion = null;
        $this->rightVersion = null;
        $this->applyDefaultVersions();
        $this->compare();
    }

    public function updatedLeftVersion(): void
    {
        $this->compare();
    }

    public function updatedRightVersion(): void
    {
        $this->compare();
    }

    public function compare(): void
    {
        if (! $this->promptName || ! $this->leftVersion || ! $this->rightVersion) {
            $this->leftPrompt = null;
            $this->rightPrompt = null;
            $this->resetDiffs();

            return;
        }

        $left = Prompt::query()
            ->where('name', $this->promptName)
            ->where('version', $this->leftVersion)
            ->first();
        $right = Prompt::query()
            ->where('name', $this->promptName)
            ->where('version', $this->rightVersion)
            ->first();

        if (! $left || ! $right) {
            $this->leftPrompt = null;
            $this->rightPrompt = null;
            $this->resetDiffs();

            return;
        }

        $this->leftPrompt = [
            'name' => (string) $left->name,
            'version' => (string) $left->version,
            'template' => (string) $left->template,
            'parameters' => is_array($left->parameters) ? $left->parameters : [],
            'is_active' => (bool) $left->is_active,
            'updated_at' => $left->updated_at?->toDateTimeString(),
        ];
        $this->rightPrompt = [
            'name' => (string) $right->name,
            'version' => (string) $right->version,
            'template' => (string) $right->template,
            'parameters' => is_array($right->parameters) ? $right->parameters : [],
            'is_active' => (bool) $right->is_active,
            'updated_at' => $right->updated_at?->toDateTimeString(),
        ];

        $this->parameterDiff = $this->buildParameterDiff(
            (array) $this->leftPrompt['parameters'],
            (array) $this->rightPrompt['parameters'],
        );
        $this->templateDiff = $this->buildTemplateDiff(
            (string) $this->leftPrompt['template'],
            (string) $this->rightPrompt['template'],
        );
    }

    /**
     * @return array<int, string>
     */
    private function resolveVersionOptions(): array
    {
        if (! $this->promptName) {
            return [];
        }

        return Prompt::query()
            ->where('name', $this->promptName)
            ->orderByDesc('id')
            ->pluck('version')
            ->all();
    }

    private function applyDefaultVersions(): void
    {
        if ($this->versionOptions === []) {
            return;
        }

        if (! $this->leftVersion || ! in_array($this->leftVersion, $this->versionOptions, true)) {
            $this->leftVersion = $this->versionOptions[0] ?? null;
        }

        if (! $this->rightVersion || ! in_array($this->rightVersion, $this->versionOptions, true)) {
            $this->rightVersion = $this->versionOptions[1] ?? ($this->versionOptions[0] ?? null);
        }
    }

    private function resetDiffs(): void
    {
        $this->parameterDiff = [
            'added' => [],
            'removed' => [],
            'changed' => [],
            'unchanged' => [],
        ];
        $this->templateDiff = [
            'left_lines' => 0,
            'right_lines' => 0,
            'added_preview' => [],
            'removed_preview' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $left
     * @param  array<string, mixed>  $right
     * @return array{added: list<string>, removed: list<string>, changed: list<string>, unchanged: list<string>}
     */
    private function buildParameterDiff(array $left, array $right): array
    {
        $leftKeys = collect(array_keys($left));
        $rightKeys = collect(array_keys($right));
        $allKeys = $leftKeys->merge($rightKeys)->unique()->sort()->values();

        $added = [];
        $removed = [];
        $changed = [];
        $unchanged = [];

        foreach ($allKeys as $key) {
            $leftHas = array_key_exists((string) $key, $left);
            $rightHas = array_key_exists((string) $key, $right);

            if ($leftHas && ! $rightHas) {
                $removed[] = (string) $key;

                continue;
            }

            if (! $leftHas && $rightHas) {
                $added[] = (string) $key;

                continue;
            }

            if (json_encode($left[$key]) !== json_encode($right[$key])) {
                $changed[] = (string) $key;
            } else {
                $unchanged[] = (string) $key;
            }
        }

        return [
            'added' => $added,
            'removed' => $removed,
            'changed' => $changed,
            'unchanged' => $unchanged,
        ];
    }

    /**
     * @return array{left_lines: int, right_lines: int, added_preview: list<string>, removed_preview: list<string>}
     */
    private function buildTemplateDiff(string $leftTemplate, string $rightTemplate): array
    {
        $leftLines = $this->splitLines($leftTemplate);
        $rightLines = $this->splitLines($rightTemplate);

        $added = collect(array_values(array_diff($rightLines, $leftLines)))
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->take(12)
            ->values()
            ->all();

        $removed = collect(array_values(array_diff($leftLines, $rightLines)))
            ->filter(fn (string $line): bool => trim($line) !== '')
            ->take(12)
            ->values()
            ->all();

        return [
            'left_lines' => count($leftLines),
            'right_lines' => count($rightLines),
            'added_preview' => $added,
            'removed_preview' => $removed,
        ];
    }

    /**
     * @return list<string>
     */
    private function splitLines(string $text): array
    {
        $lines = preg_split('/\R/', str_replace("\r\n", "\n", $text)) ?: [];

        return collect($lines)
            ->map(fn (string $line): string => rtrim($line))
            ->all();
    }
}
