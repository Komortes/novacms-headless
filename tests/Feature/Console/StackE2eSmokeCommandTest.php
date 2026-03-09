<?php

namespace Tests\Feature\Console;

use App\Services\RuntimeE2eSmokeService;
use Mockery;
use Tests\TestCase;

class StackE2eSmokeCommandTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();

        parent::tearDown();
    }

    public function test_stack_e2e_smoke_command_returns_success(): void
    {
        $service = Mockery::mock(RuntimeE2eSmokeService::class);
        $service->shouldReceive('run')->once()->andReturn([
            'ok' => true,
            'steps' => [
                ['name' => 'health', 'status' => 'ok', 'message' => 'healthy', 'meta' => []],
                ['name' => 'search', 'status' => 'ok', 'message' => 'search ok', 'meta' => ['score' => 0.99]],
            ],
            'content_id' => null,
            'slug' => null,
            'generated_at' => now()->toIso8601String(),
        ]);

        $this->app->instance(RuntimeE2eSmokeService::class, $service);

        $this->artisan('stack:e2e-smoke')
            ->expectsTable(
                ['Step', 'Status', 'Message', 'Meta'],
                [
                    ['health', 'OK', 'healthy', '-'],
                    ['search', 'OK', 'search ok', '{"score":0.99}'],
                ],
            )
            ->assertExitCode(0);
    }

    public function test_stack_e2e_smoke_command_returns_failure_json(): void
    {
        $service = Mockery::mock(RuntimeE2eSmokeService::class);
        $service->shouldReceive('run')->once()->andReturn([
            'ok' => false,
            'steps' => [
                ['name' => 'failure', 'status' => 'failed', 'message' => 'redis unreachable', 'meta' => []],
            ],
            'content_id' => null,
            'slug' => null,
            'generated_at' => now()->toIso8601String(),
        ]);

        $this->app->instance(RuntimeE2eSmokeService::class, $service);

        $this->artisan('stack:e2e-smoke --json')
            ->assertExitCode(1);
    }
}
