<?php

namespace Tests\Feature\Filament;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class AdminAccessControlTest extends TestCase
{
    use RefreshDatabase;

    protected Content $content;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('ai.summary.auto_dispatch', false);
        Config::set('ai.embeddings.auto_dispatch', false);

        $this->content = Content::query()->create([
            'type' => ContentType::POST,
            'slug' => 'rbac-sample',
            'title' => 'RBAC Sample',
            'body' => "# RBAC Sample\n\nTesting Filament access.",
            'locale' => 'en',
            'status' => ContentStatus::DRAFT,
        ]);
    }

    public function test_admin_can_access_system_and_registry_pages(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get('/admin/settings/ai')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/settings/api-access')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/prompts')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/queue-center')
            ->assertOk();

        $this->actingAs($admin)
            ->get('/admin/system-health')
            ->assertOk();
    }

    public function test_editor_can_work_with_content_but_not_system_pages(): void
    {
        $editor = User::factory()->editor()->create();

        $this->actingAs($editor)
            ->get('/admin/contents')
            ->assertOk()
            ->assertSee('FAQ & info')
            ->assertDontSee('Load demo data')
            ->assertDontSee('API access')
            ->assertDontSee('AI settings');

        $this->actingAs($editor)
            ->get('/admin/contents/create')
            ->assertOk();

        $this->actingAs($editor)
            ->get("/admin/contents/{$this->content->id}/edit")
            ->assertOk();

        $this->actingAs($editor)
            ->get('/admin/queue-center')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/settings/ai')
            ->assertForbidden();

        $this->actingAs($editor)
            ->get('/admin/prompts')
            ->assertForbidden();
    }

    public function test_operator_gets_queue_and_runtime_access_but_not_editor_mutations(): void
    {
        $operator = User::factory()->operator()->create();

        $this->actingAs($operator)
            ->get('/admin/contents')
            ->assertOk()
            ->assertSee('Queue')
            ->assertDontSee('Load demo data')
            ->assertDontSee('API access')
            ->assertDontSee('AI settings');

        $this->actingAs($operator)
            ->get("/admin/contents/{$this->content->id}")
            ->assertOk()
            ->assertSee('Generate summary')
            ->assertDontSee('Set status');

        $this->actingAs($operator)
            ->get('/admin/queue-center')
            ->assertOk();

        $this->actingAs($operator)
            ->get('/admin/system-health')
            ->assertOk();

        $this->actingAs($operator)
            ->get('/admin/contents/create')
            ->assertForbidden();

        $this->actingAs($operator)
            ->get("/admin/contents/{$this->content->id}/edit")
            ->assertForbidden();

        $this->actingAs($operator)
            ->get('/admin/settings/ai')
            ->assertForbidden();

        $this->actingAs($operator)
            ->get('/admin/prompts')
            ->assertForbidden();
    }
}
