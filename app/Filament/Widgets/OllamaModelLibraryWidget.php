<?php

namespace App\Filament\Widgets;

use App\Jobs\PullOllamaModelJob;
use App\Models\ModelPullTask;
use App\Services\OllamaModelService;
use App\Support\AdminPanelAccess;
use Filament\Notifications\Notification;
use Filament\Widgets\Widget;

class OllamaModelLibraryWidget extends Widget
{
    protected string $view = 'filament.widgets.ollama-model-library-widget';

    protected int|string|array $columnSpan = 'full';

    protected static ?int $sort = 20;

    public string $newModelName = '';

    public static function canView(): bool
    {
        return AdminPanelAccess::canManageAiSettings();
    }

    public function startPull(): void
    {
        abort_unless(AdminPanelAccess::canManageAiSettings(), 403);

        $model = trim($this->newModelName);

        if ($model === '') {
            Notification::make()->title('Enter a model name')->warning()->send();

            return;
        }

        $alreadyActive = ModelPullTask::query()
            ->where('model', $model)
            ->whereIn('status', ['pending', 'pulling'])
            ->exists();

        if ($alreadyActive) {
            Notification::make()
                ->title("Pull for {$model} is already in progress")
                ->warning()
                ->send();

            return;
        }

        $task = ModelPullTask::create([
            'model' => $model,
            'status' => 'pending',
            'progress' => 0,
            'status_text' => 'Queued',
        ]);

        PullOllamaModelJob::dispatch($task->id)->onQueue('ai');

        $this->newModelName = '';

        Notification::make()
            ->title("Pulling {$model}")
            ->body('Job dispatched. Progress updates every 5 seconds.')
            ->success()
            ->send();
    }

    public function deleteModel(string $model): void
    {
        abort_unless(AdminPanelAccess::canManageAiSettings(), 403);

        $deleted = app(OllamaModelService::class)->deleteModel($model);

        if ($deleted) {
            Notification::make()->title("Deleted {$model}")->success()->send();
        } else {
            Notification::make()->title("Could not delete {$model}")->danger()->send();
        }
    }

    public function dismissTask(int $taskId): void
    {
        abort_unless(AdminPanelAccess::canManageAiSettings(), 403);

        ModelPullTask::query()
            ->where('id', $taskId)
            ->whereIn('status', ['success', 'failed'])
            ->delete();
    }

    public function pullFromCatalog(string $modelId): void
    {
        $this->newModelName = $modelId;
        $this->startPull();
    }

    /**
     * @return array<string, mixed>
     */
    protected function getViewData(): array
    {
        $service = app(OllamaModelService::class);

        $activePulls = ModelPullTask::query()
            ->whereIn('status', ['pending', 'pulling'])
            ->orderByDesc('id')
            ->get(['id', 'model', 'status', 'progress', 'status_text', 'updated_at'])
            ->all();

        $recentPulls = ModelPullTask::query()
            ->whereIn('status', ['success', 'failed'])
            ->orderByDesc('id')
            ->limit(5)
            ->get(['id', 'model', 'status', 'status_text', 'error', 'updated_at'])
            ->all();

        $installedModels = $service->listInstalledModels();

        $installedNames = array_flip(array_map(
            fn (array $m): string => $m['name'],
            $installedModels,
        ));

        $activePullModels = array_flip(array_map(
            fn (ModelPullTask $t): string => $t->model,
            $activePulls,
        ));

        $catalog = array_map(function (array $entry) use ($installedNames, $activePullModels): array {
            $entry['installed'] = array_key_exists($entry['id'], $installedNames);
            $entry['pulling'] = array_key_exists($entry['id'], $activePullModels);

            return $entry;
        }, OllamaModelService::catalog());

        $catalogByCategory = [
            'generation' => ['tiny' => [], 'small' => [], 'medium' => []],
            'embeddings' => [],
        ];

        foreach ($catalog as $entry) {
            if ($entry['category'] === 'embeddings') {
                $catalogByCategory['embeddings'][] = $entry;
            } elseif ($entry['tone'] === 'sky') {
                $catalogByCategory['generation']['tiny'][] = $entry;
            } elseif ($entry['tone'] === 'indigo') {
                $catalogByCategory['generation']['small'][] = $entry;
            } else {
                $catalogByCategory['generation']['medium'][] = $entry;
            }
        }

        return [
            'installedModels' => $installedModels,
            'activePulls' => $activePulls,
            'recentPulls' => $recentPulls,
            'ollamaReachable' => $service->isReachable(),
            'catalog' => $catalog,
            'catalogByCategory' => $catalogByCategory,
        ];
    }
}
