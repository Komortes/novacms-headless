<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\Prompt;
use App\Models\User;
use Database\Seeders\DemoEnvironmentSeeder;
use Illuminate\Support\Facades\Artisan;

class DemoWorkspaceService
{
    public function __construct(
        private readonly RuntimeHealthService $runtimeHealthService,
    ) {}

    /**
     * @return array{
     *     ok: bool,
     *     scenario_ok: bool,
     *     runtime_ok: bool,
     *     summary: array{
     *         demo_users_found: int,
     *         demo_users_expected: int,
     *         content: int,
     *         published: int,
     *         drafts: int,
     *         ready_summaries: int,
     *         failed_summaries: int,
     *         active_prompts: int
     *     },
     *     scenario_checks: list<array{label: string, status: string, message: string}>,
     *     runtime_checks: list<array{component: string, status: string, message: string, meta: array<string, mixed>}>,
     *     alerts: list<array{code: string, severity: string, title: string, message: string, value: string|int|float, threshold: string|int|float}>,
     *     generated_at: string
     * }
     */
    public function report(bool $withRuntime = true): array
    {
        $demoEmails = [
            DemoEnvironmentSeeder::ADMIN_EMAIL,
            DemoEnvironmentSeeder::EDITOR_EMAIL,
            DemoEnvironmentSeeder::OPERATOR_EMAIL,
        ];

        $foundDemoUsers = User::query()
            ->whereIn('email', $demoEmails)
            ->pluck('email')
            ->all();

        $contentCount = Content::query()->count();
        $publishedCount = Content::query()
            ->where('status', ContentStatus::PUBLISHED->value)
            ->count();
        $draftCount = Content::query()
            ->where('status', ContentStatus::DRAFT->value)
            ->count();
        $readySummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::READY->value)
            ->count();
        $failedSummaries = ContentAiSummary::query()
            ->where('status', SummaryStatus::FAILED->value)
            ->count();
        $activePrompts = Prompt::query()
            ->where('is_active', true)
            ->count();

        $scenarioChecks = [
            $this->scenarioCheck(
                label: 'Admin login',
                ok: in_array(DemoEnvironmentSeeder::ADMIN_EMAIL, $foundDemoUsers, true),
                successMessage: 'Seeded admin account is available.',
                failureMessage: 'Seeded admin account is missing.',
            ),
            $this->scenarioCheck(
                label: 'Editor login',
                ok: in_array(DemoEnvironmentSeeder::EDITOR_EMAIL, $foundDemoUsers, true),
                successMessage: 'Seeded editor account is available.',
                failureMessage: 'Seeded editor account is missing.',
            ),
            $this->scenarioCheck(
                label: 'Operator login',
                ok: in_array(DemoEnvironmentSeeder::OPERATOR_EMAIL, $foundDemoUsers, true),
                successMessage: 'Seeded operator account is available.',
                failureMessage: 'Seeded operator account is missing.',
            ),
            $this->scenarioCheck(
                label: 'Seeded content bundle',
                ok: $contentCount >= 4,
                successMessage: sprintf('%d content records are present for the walkthrough.', $contentCount),
                failureMessage: sprintf('Expected at least 4 seeded content records, found %d.', $contentCount),
            ),
            $this->scenarioCheck(
                label: 'Published examples',
                ok: $publishedCount >= 1,
                successMessage: sprintf('%d published records are available for the headless/API story.', $publishedCount),
                failureMessage: 'No published content is available for the headless/API story.',
            ),
            $this->scenarioCheck(
                label: 'Draft example',
                ok: $draftCount >= 1,
                successMessage: sprintf('%d draft record(s) are available for editorial review.', $draftCount),
                failureMessage: 'No draft content is available for the editorial workflow.',
            ),
            $this->scenarioCheck(
                label: 'Ready AI examples',
                ok: $readySummaries >= 1,
                successMessage: sprintf('%d ready AI summaries are available to inspect.', $readySummaries),
                failureMessage: 'No ready AI summaries are available to inspect.',
            ),
            $this->scenarioCheck(
                label: 'Failed AI example',
                ok: $failedSummaries >= 1,
                successMessage: sprintf('%d failed AI summary example(s) are available for ops walkthrough.', $failedSummaries),
                failureMessage: 'No failed AI example is available for the ops walkthrough.',
            ),
            $this->scenarioCheck(
                label: 'Prompt baseline',
                ok: $activePrompts >= 1,
                successMessage: sprintf('%d active prompt contract(s) are available.', $activePrompts),
                failureMessage: 'No active prompt contract is available.',
            ),
        ];

        $scenarioOk = collect($scenarioChecks)->every(
            fn (array $check): bool => $check['status'] === 'ok'
        );

        $runtimeChecks = [];
        $alerts = [];
        $runtimeOk = true;

        if ($withRuntime) {
            $runtime = $this->runtimeHealthService->collect();
            $runtimeChecks = collect((array) ($runtime['checks'] ?? []))
                ->map(fn (array $check): array => $this->normalizeDemoRuntimeCheck($check))
                ->values()
                ->all();
            $runtimeOk = collect($runtimeChecks)
                ->every(fn (array $check): bool => (string) ($check['status'] ?? 'fail') !== 'fail');
            $alerts = $runtime['alerts'];
        }

        return [
            'ok' => $scenarioOk && $runtimeOk,
            'scenario_ok' => $scenarioOk,
            'runtime_ok' => $runtimeOk,
            'summary' => [
                'demo_users_found' => count($foundDemoUsers),
                'demo_users_expected' => count($demoEmails),
                'content' => $contentCount,
                'published' => $publishedCount,
                'drafts' => $draftCount,
                'ready_summaries' => $readySummaries,
                'failed_summaries' => $failedSummaries,
                'active_prompts' => $activePrompts,
            ],
            'scenario_checks' => $scenarioChecks,
            'runtime_checks' => $runtimeChecks,
            'alerts' => $alerts,
            'generated_at' => now()->toIso8601String(),
        ];
    }

    /**
     * @return array{
     *     ok: bool,
     *     scenario_ok: bool,
     *     runtime_ok: bool,
     *     summary: array{
     *         demo_users_found: int,
     *         demo_users_expected: int,
     *         content: int,
     *         published: int,
     *         drafts: int,
     *         ready_summaries: int,
     *         failed_summaries: int,
     *         active_prompts: int
     *     },
     *     scenario_checks: list<array{label: string, status: string, message: string}>,
     *     runtime_checks: list<array{component: string, status: string, message: string, meta: array<string, mixed>}>,
     *     alerts: list<array{code: string, severity: string, title: string, message: string, value: string|int|float, threshold: string|int|float}>,
     *     generated_at: string
     * }
     */
    public function reset(bool $withRuntime = false): array
    {
        Artisan::call('migrate:fresh', ['--force' => true]);
        Artisan::call('db:seed', [
            '--class' => DemoEnvironmentSeeder::class,
            '--force' => true,
        ]);
        Artisan::call('view:cache');

        return $this->report($withRuntime);
    }

    /**
     * @return array{label: string, status: string, message: string}
     */
    private function scenarioCheck(
        string $label,
        bool $ok,
        string $successMessage,
        string $failureMessage,
    ): array {
        return [
            'label' => $label,
            'status' => $ok ? 'ok' : 'missing',
            'message' => $ok ? $successMessage : $failureMessage,
        ];
    }

    /**
     * @param  array{component: string, status: string, message: string, meta: array<string, mixed>}  $check
     * @return array{component: string, status: string, message: string, meta: array<string, mixed>}
     */
    private function normalizeDemoRuntimeCheck(array $check): array
    {
        $component = strtolower((string) ($check['component'] ?? ''));
        $status = (string) ($check['status'] ?? 'fail');
        $meta = (array) ($check['meta'] ?? []);

        if (
            $component === 'ollama'
            && $status === 'fail'
            && (($meta['missing_models'] ?? []) !== [])
        ) {
            $check['status'] = 'warn';
            $check['message'] = 'Ollama is reachable, but demo models are not installed yet. The seeded walkthrough still works; run `make demo-models` for live generation.';
        }

        return $check;
    }
}
