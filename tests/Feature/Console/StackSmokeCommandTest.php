<?php

namespace Tests\Feature\Console;

use App\Services\RuntimeHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class StackSmokeCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_stack_smoke_command_returns_success_when_all_checks_pass(): void
    {
        $service = Mockery::mock(RuntimeHealthService::class);
        $service->shouldReceive('collect')->once()->andReturn([
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
        $this->app->instance(RuntimeHealthService::class, $service);

        $this->artisan('stack:smoke --json')
            ->assertExitCode(0);
    }

    public function test_stack_smoke_command_returns_failure_when_any_check_fails(): void
    {
        $service = Mockery::mock(RuntimeHealthService::class);
        $service->shouldReceive('collect')->once()->andReturn([
            'ok' => false,
            'checks' => [
                [
                    'component' => 'Ollama',
                    'status' => 'fail',
                    'message' => 'failed',
                    'meta' => [],
                ],
            ],
            'alerts' => [
                [
                    'code' => 'queue_depth',
                    'severity' => 'danger',
                    'title' => 'Queue depth is high',
                    'message' => 'Too high',
                    'value' => 20,
                    'threshold' => 10,
                ],
            ],
            'generated_at' => now()->toIso8601String(),
        ]);
        $this->app->instance(RuntimeHealthService::class, $service);

        $this->artisan('stack:smoke')
            ->assertExitCode(1);
    }
}

