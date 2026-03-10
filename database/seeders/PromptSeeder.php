<?php

namespace Database\Seeders;

use App\Services\PromptRegistry;
use Illuminate\Database\Seeder;

class PromptSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $registry = app(PromptRegistry::class);

        $registry->upsert(
            name: 'content.summary',
            version: '1.0.0',
            template: <<<'PROMPT'
You are an expert content assistant for a headless CMS.
Given markdown content, return strict JSON with:
- summary_tldr (string)
- summary_bullets (array of concise strings)
- summary_meta_description (max 155 chars)
- summary_faq (array of objects: question, answer)
- summary_tags (array of short tags)

Rules:
- Keep facts grounded in provided content.
- Do not invent details.
- Use clear, editorial language.
PROMPT,
            parameters: [
                'max_bullets' => 6,
                'max_tags' => 8,
                'faq_items' => 5,
                'max_meta_description_chars' => 155,
            ],
            isActive: true,
        );

        $registry->upsert(
            name: 'content.summary.map',
            version: '1.0.0',
            template: <<<'PROMPT'
Summarize the chunk into strict JSON:
- key_points: array of concise factual statements
- candidate_faq: array of objects: question, answer
- candidate_tags: array of short tags
PROMPT,
            parameters: [
                'max_key_points' => 8,
                'faq_items' => 3,
                'max_tags' => 5,
            ],
            isActive: true,
        );

        $registry->upsert(
            name: 'content.summary.reduce',
            version: '1.0.0',
            template: <<<'PROMPT'
Merge partial map outputs into strict JSON:
- summary_tldr
- summary_bullets
- summary_meta_description
- summary_faq
- summary_tags

Rules:
- remove duplicates
- keep only high-signal points
- maintain consistency and factuality
PROMPT,
            parameters: [
                'max_bullets' => 6,
                'faq_items' => 5,
                'max_tags' => 8,
                'max_meta_description_chars' => 155,
            ],
            isActive: true,
        );
    }
}
