<?php

namespace Tests\Feature;

use App\Models\Prompt;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_only_one_active_prompt_version_per_name_is_kept(): void
    {
        $first = Prompt::query()->create([
            'name' => 'content.summary',
            'version' => '1.0.0',
            'template' => 'Template A',
            'parameters' => ['a' => 1],
            'is_active' => true,
        ]);

        $second = Prompt::query()->create([
            'name' => 'content.summary',
            'version' => '1.1.0',
            'template' => 'Template B',
            'parameters' => ['b' => 1],
            'is_active' => true,
        ]);

        $this->assertTrue($second->fresh()->is_active);
        $this->assertFalse((bool) $first->fresh()?->is_active);
    }
}
