<?php

namespace Tests\Feature\Console;

use App\Services\DemoWorkspaceService;
use App\Services\RuntimeHealthService;
use Database\Seeders\DemoEnvironmentSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Mockery;
use Tests\TestCase;

class DemoCommandsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config()->set('ai.summary.auto_dispatch', false);
        config()->set('ai.embeddings.auto_dispatch', false);
    }

    public function test_demo_check_command_passes_for_seeded_demo_state(): void
    {
        $this->seed(DemoEnvironmentSeeder::class);

        $health = Mockery::mock(RuntimeHealthService::class);
        $health->shouldReceive('collect')->once()->andReturn([
            'ok' => true,
            'checks' => [
                [
                    'component' => 'Database',
                    'status' => 'ok',
                    'message' => 'ok',
                    'meta' => [],
                ],
            ],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $health);

        $exitCode = Artisan::call('demo:check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"ok": true', $output);
        $this->assertStringContainsString('"scenario_ok": true', $output);
        $this->assertStringContainsString('"demo_users_found": 3', $output);
    }

    public function test_demo_check_command_fails_when_seeded_story_is_missing(): void
    {
        $health = Mockery::mock(RuntimeHealthService::class);
        $health->shouldReceive('collect')->once()->andReturn([
            'ok' => true,
            'checks' => [
                [
                    'component' => 'Database',
                    'status' => 'ok',
                    'message' => 'ok',
                    'meta' => [],
                ],
            ],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $health);

        $exitCode = Artisan::call('demo:check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(1, $exitCode);
        $this->assertStringContainsString('"ok": false', $output);
        $this->assertStringContainsString('"scenario_ok": false', $output);
    }

    public function test_demo_check_treats_missing_ollama_models_as_demo_warning(): void
    {
        $this->seed(DemoEnvironmentSeeder::class);

        $health = Mockery::mock(RuntimeHealthService::class);
        $health->shouldReceive('collect')->once()->andReturn([
            'ok' => false,
            'checks' => [
                [
                    'component' => 'Database',
                    'status' => 'ok',
                    'message' => 'ok',
                    'meta' => [],
                ],
                [
                    'component' => 'Ollama',
                    'status' => 'fail',
                    'message' => 'Ollama is reachable but required models are missing.',
                    'meta' => [
                        'missing_models' => ['qwen2.5:1.5b', 'nomic-embed-text'],
                    ],
                ],
            ],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $health);

        $exitCode = Artisan::call('demo:check', ['--json' => true]);
        $output = Artisan::output();

        $this->assertSame(0, $exitCode);
        $this->assertStringContainsString('"runtime_ok": true', $output);
        $this->assertStringContainsString('"status": "warn"', $output);
        $this->assertStringContainsString('make demo-models', $output);
    }

    public function test_demo_reset_command_reports_restored_seed_state(): void
    {
        $workspace = Mockery::mock(DemoWorkspaceService::class);
        $workspace->shouldReceive('reset')->once()->andReturn([
            'ok' => true,
            'scenario_ok' => true,
            'runtime_ok' => true,
            'summary' => [
                'demo_users_found' => 3,
                'demo_users_expected' => 3,
                'content' => 4,
                'published' => 3,
                'drafts' => 1,
                'ready_summaries' => 3,
                'failed_summaries' => 1,
                'active_prompts' => 1,
            ],
            'scenario_checks' => [],
            'runtime_checks' => [],
            'alerts' => [],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(DemoWorkspaceService::class, $workspace);

        $this->artisan('demo:reset --force')
            ->expectsOutput('Demo environment reset.')
            ->expectsOutputToContain('Demo users: 3/3')
            ->expectsOutputToContain('Content: 4')
            ->expectsOutputToContain('Seeded demo story restored.')
            ->assertExitCode(0);
    }
}
