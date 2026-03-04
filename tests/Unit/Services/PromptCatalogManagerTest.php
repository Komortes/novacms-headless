<?php

namespace Tests\Unit\Services;

use App\Models\Prompt;
use App\Services\PromptCatalogManager;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PromptCatalogManagerTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_exports_prompts_in_bundle_format(): void
    {
        Prompt::query()->create([
            'name' => 'content.summary',
            'version' => '1.0.0',
            'template' => 'Template A',
            'parameters' => ['k' => 'v'],
            'is_active' => true,
        ]);

        Prompt::query()->create([
            'name' => 'content.summary',
            'version' => '1.1.0',
            'template' => 'Template B',
            'parameters' => ['k2' => 'v2'],
            'is_active' => false,
        ]);

        $bundle = app(PromptCatalogManager::class)->export();

        $this->assertArrayHasKey('exported_at', $bundle);
        $this->assertSame(2, $bundle['count']);
        $this->assertCount(2, $bundle['prompts']);
        $this->assertSame('content.summary', $bundle['prompts'][0]['name']);
    }

    public function test_it_imports_prompt_bundle_and_applies_active_flag(): void
    {
        $payload = json_encode([
            'prompts' => [
                [
                    'name' => 'content.summary',
                    'version' => '2.0.0',
                    'template' => 'Template V2',
                    'parameters' => ['temperature' => 0.1],
                    'is_active' => true,
                ],
                [
                    'name' => 'content.summary',
                    'version' => '1.9.0',
                    'template' => 'Template V1.9',
                    'parameters' => ['temperature' => 0.2],
                    'is_active' => false,
                ],
            ],
        ], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        $result = app(PromptCatalogManager::class)->importFromJson((string) $payload, true);

        $this->assertSame(2, $result['upserted']);
        $this->assertSame(1, $result['activated']);
        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '2.0.0',
            'is_active' => true,
        ]);
        $this->assertDatabaseHas('prompts', [
            'name' => 'content.summary',
            'version' => '1.9.0',
            'is_active' => false,
        ]);
    }
}
