<?php

namespace Database\Seeders;

use App\Services\ContentCatalogManager;
use Illuminate\Database\Seeder;

class DemoContentSeeder extends Seeder
{
    /**
     * Seed the application's database with a ready-to-demo content bundle.
     */
    public function run(): void
    {
        $this->call([
            PromptSeeder::class,
        ]);

        $path = database_path('seeders/data/demo-content.json');
        $payload = file_get_contents($path);

        if (! is_string($payload) || trim($payload) === '') {
            throw new \RuntimeException('Demo content dataset is missing or empty.');
        }

        app(ContentCatalogManager::class)->importFromJson($payload, true);
    }
}
