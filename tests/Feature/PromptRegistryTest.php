<?php

namespace Tests\Feature;

use App\Models\Prompt;
use App\Services\PromptRegistry;
use Database\Seeders\PromptSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptRegistryTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_resolves_seeded_active_prompt(): void
    {
        $this->seed(PromptSeeder::class);

        $registry = app(PromptRegistry::class);
        $prompt = $registry->resolveActive('content.summary');

        $this->assertSame('content.summary', $prompt->name);
        $this->assertSame('1.0.0', $prompt->version);
        $this->assertTrue($prompt->is_active);
    }

    public function test_upsert_active_version_deactivates_previous_versions(): void
    {
        $this->seed(PromptSeeder::class);

        $registry = app(PromptRegistry::class);
        $registry->upsert(
            name: 'content.summary',
            version: '2.0.0',
            template: 'new template',
            parameters: ['max_bullets' => 7],
            isActive: true,
        );

        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '2.0.0',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '1.0.0',
            'is_active' => false,
        ]);
    }

    public function test_activate_switches_active_prompt_version(): void
    {
        $this->seed(PromptSeeder::class);

        $registry = app(PromptRegistry::class);
        $inactive = $registry->upsert(
            name: 'content.summary',
            version: '0.9.0',
            template: 'legacy template',
            isActive: false,
        );

        $registry->activate($inactive);

        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '0.9.0',
            'is_active' => true,
        ]);

        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '1.0.0',
            'is_active' => false,
        ]);

        $active = Prompt::query()->named('content.summary')->active()->first();

        $this->assertNotNull($active);
        $this->assertSame('0.9.0', $active?->version);
    }
}

