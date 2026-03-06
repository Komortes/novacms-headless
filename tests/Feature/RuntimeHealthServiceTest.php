<?php

namespace Tests\Feature;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\ContentAiSummaryEvent;
use App\Services\RuntimeHealthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class RuntimeHealthServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_queue_alerts_are_raised_when_thresholds_are_exceeded(): void
    {
        config()->set('ops.alerts.queue_depth_threshold', 1);
        config()->set('ops.alerts.queue_lag_minutes_threshold', 10);
        config()->set('ops.alerts.failed_absolute_per_hour_threshold', 2);
        config()->set('ops.alerts.failed_growth_per_hour_threshold', 1);

        $content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'health-alert-content',
            'title' => 'Health Alert Content',
            'body' => 'Body',
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);

        DB::table('content_ai_summaries')
            ->where('id', $content->summary?->id)
            ->update([
                'updated_at' => now()->subMinutes(25),
            ]);

        ContentAiSummaryEvent::query()->create([
            'content_id' => $content->id,
            'content_ai_summary_id' => $content->summary?->id,
            'event' => 'failed',
            'created_at' => now()->subMinutes(10),
        ]);
        ContentAiSummaryEvent::query()->create([
            'content_id' => $content->id,
            'content_ai_summary_id' => $content->summary?->id,
            'event' => 'failed',
            'created_at' => now()->subMinutes(20),
        ]);
        ContentAiSummaryEvent::query()->create([
            'content_id' => $content->id,
            'content_ai_summary_id' => $content->summary?->id,
            'event' => 'failed',
            'created_at' => now()->subMinutes(30),
        ]);
        ContentAiSummaryEvent::query()->create([
            'content_id' => $content->id,
            'content_ai_summary_id' => $content->summary?->id,
            'event' => 'failed',
            'created_at' => now()->subMinutes(90),
        ]);

        $alerts = app(RuntimeHealthService::class)->queueAlerts();
        $codes = collect($alerts)->pluck('code')->all();

        $this->assertContains('queue_depth', $codes);
        $this->assertContains('queue_lag', $codes);
        $this->assertContains('failed_absolute', $codes);
        $this->assertContains('failed_growth', $codes);
    }

    public function test_queue_alerts_are_empty_when_system_is_within_thresholds(): void
    {
        config()->set('ops.alerts.queue_depth_threshold', 100);
        config()->set('ops.alerts.queue_lag_minutes_threshold', 120);
        config()->set('ops.alerts.failed_absolute_per_hour_threshold', 100);
        config()->set('ops.alerts.failed_growth_per_hour_threshold', 100);

        $alerts = app(RuntimeHealthService::class)->queueAlerts();

        $this->assertSame([], $alerts);
    }
}
