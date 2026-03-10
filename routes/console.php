<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\ApiAccessToken;
use App\Models\Content;
use App\Models\User;
use App\Services\ContentCatalogManager;
use App\Services\ContentEmbeddingDispatcher;
use App\Services\ContentEmbeddingGenerator;
use App\Services\ContentEmbeddingReindexer;
use App\Services\ContentSummaryDispatcher;
use App\Services\ContentSummaryGenerator;
use App\Services\RuntimeE2eSmokeService;
use App\Services\RuntimeHealthService;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content:generate-summary {content : Content ID or slug} {--prompt-version=} {--provider=} {--model=} {--sync}', function () {
    $contentArgument = (string) $this->argument('content');
    $provider = is_string($this->option('provider')) && trim((string) $this->option('provider')) !== ''
        ? trim((string) $this->option('provider'))
        : null;
    $model = is_string($this->option('model')) && trim((string) $this->option('model')) !== ''
        ? trim((string) $this->option('model'))
        : null;
    $promptVersion = is_string($this->option('prompt-version')) && trim((string) $this->option('prompt-version')) !== ''
        ? trim((string) $this->option('prompt-version'))
        : null;
    $sync = (bool) $this->option('sync');

    /** @var Content|null $content */
    $content = ctype_digit($contentArgument)
        ? Content::query()->find((int) $contentArgument)
        : Content::query()->where('slug', $contentArgument)->first();

    if (! $content) {
        $this->error("Content not found for [{$contentArgument}].");

        $available = Content::query()
            ->select(['id', 'slug', 'locale', 'status'])
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($available->isEmpty()) {
            $this->line('No content records exist yet.');
            $this->line('Create one quickly with: php artisan content:create-sample');
        } else {
            $this->line('Available content records:');
            foreach ($available as $record) {
                $this->line("- id={$record->id}, slug={$record->slug}, locale={$record->locale}, status={$record->status->value}");
            }
        }

        return Command::FAILURE;
    }

    if (! $sync) {
        try {
            app(ContentSummaryDispatcher::class)->dispatch(
                content: $content,
                provider: $provider,
                model: $model,
                promptVersion: $promptVersion,
            );
        } catch (Throwable $exception) {
            $this->error('Summary queueing failed: '.$exception->getMessage());

            return Command::FAILURE;
        }

        $this->info('Summary generation queued.');
        $this->line('Content ID: '.$content->id);
        $this->line('Status: pending');
        $this->line('Provider: '.($provider ?? (string) config('ai.provider', 'ollama')));
        $this->line('Model: '.($model ?? 'default'));
        $this->line('Prompt version: '.($promptVersion ?? 'active'));

        return Command::SUCCESS;
    }

    if ($provider !== null) {
        config()->set('ai.provider', $provider);
    }

    /** @var ContentSummaryGenerator $generator */
    $generator = app(ContentSummaryGenerator::class);
    try {
        $generationOptions = [];

        if ($model !== null) {
            $generationOptions['model'] = $model;
        }

        $summary = $generator->generateForContent(
            $content,
            $promptVersion,
            $generationOptions,
        );
    } catch (Throwable $exception) {
        $this->error('Summary generation failed: '.$exception->getMessage());

        return Command::FAILURE;
    }

    $this->info('Summary generated.');
    $this->line('Content ID: '.$content->id);
    $this->line('Summary ID: '.$summary->id);
    $this->line('Status: '.$summary->status->value);
    $this->line('Model: '.($summary->model ?? 'n/a'));
    $this->line('Prompt version: '.($summary->prompt_version ?? 'n/a'));

    return Command::SUCCESS;
})->purpose('Queue content AI summary generation (default), or run synchronously with --sync');

Artisan::command('content:create-sample {--slug=sample-post} {--title=Sample Post} {--body=Sample markdown content for NovaCMS.} {--locale=en} {--status=draft} {--type=post}', function () {
    $type = ContentType::tryFrom((string) $this->option('type'));
    $status = ContentStatus::tryFrom((string) $this->option('status'));

    if (! $type) {
        $this->error('Invalid --type. Allowed: post, page');

        return Command::FAILURE;
    }

    if (! $status) {
        $this->error('Invalid --status. Allowed: draft, published, archived');

        return Command::FAILURE;
    }

    $content = Content::query()->create([
        'type' => $type,
        'slug' => (string) $this->option('slug'),
        'title' => (string) $this->option('title'),
        'body' => (string) $this->option('body'),
        'locale' => (string) $this->option('locale'),
        'status' => $status,
    ]);

    $this->info('Sample content created.');
    $this->line('Content ID: '.$content->id);
    $this->line('Slug: '.$content->slug);

    return Command::SUCCESS;
})->purpose('Create a sample content record for local AI summary testing');

Artisan::command('content:export {--path=} {--type=} {--status=} {--locale=} {--no-summaries}', function (ContentCatalogManager $catalog): int {
    $payload = $catalog->export(
        type: is_string($this->option('type')) && trim((string) $this->option('type')) !== '' ? trim((string) $this->option('type')) : null,
        status: is_string($this->option('status')) && trim((string) $this->option('status')) !== '' ? trim((string) $this->option('status')) : null,
        locale: is_string($this->option('locale')) && trim((string) $this->option('locale')) !== '' ? trim((string) $this->option('locale')) : null,
        withSummaries: ! (bool) $this->option('no-summaries'),
    );

    $json = json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if (! is_string($json)) {
        $this->error('Failed to encode content export payload.');

        return Command::FAILURE;
    }

    $path = is_string($this->option('path')) && trim((string) $this->option('path')) !== ''
        ? trim((string) $this->option('path'))
        : storage_path('app/exports/content-bundle-'.now()->format('Ymd-His').'.json');

    File::ensureDirectoryExists(dirname($path));
    File::put($path, $json);

    $this->info('Content bundle exported.');
    $this->line('Path: '.$path);
    $this->line('Items: '.$payload['count']);
    $this->line('Included summaries: '.((bool) $this->option('no-summaries') ? 'no' : 'yes'));

    return Command::SUCCESS;
})->purpose('Export content records to a reusable JSON bundle');

Artisan::command('content:import {source? : Path to content bundle JSON} {--demo : Import the bundled demo dataset} {--no-upsert}', function (ContentCatalogManager $catalog): int {
    $useDemo = (bool) $this->option('demo');
    $source = $this->argument('source');

    if (! $useDemo && (! is_string($source) || trim($source) === '')) {
        $this->error('Provide a JSON file path or use --demo.');

        return Command::FAILURE;
    }

    $path = $useDemo
        ? database_path('seeders/data/demo-content.json')
        : trim((string) $source);

    if (! File::exists($path)) {
        $this->error("Content bundle not found at [{$path}].");

        return Command::FAILURE;
    }

    $payload = File::get($path);

    try {
        $result = $catalog->importFromJson($payload, ! (bool) $this->option('no-upsert'));
    } catch (Throwable $exception) {
        $this->error('Content import failed: '.$exception->getMessage());

        return Command::FAILURE;
    }

    $this->info($useDemo ? 'Demo content imported.' : 'Content bundle imported.');
    $this->line('Path: '.$path);
    $this->line('Imported: '.$result['imported']);
    $this->line('Created: '.$result['created']);
    $this->line('Updated: '.$result['updated']);
    $this->line('Summaries: '.$result['summaries']);
    $this->line('Skipped: '.$result['skipped']);

    return Command::SUCCESS;
})->purpose('Import content records from JSON or load the bundled demo dataset');

Artisan::command('api-token:create {user : User ID or email} {name : Token label} {--ability=* : Repeatable token abilities} {--expires-days=}', function () {
    $userArgument = trim((string) $this->argument('user'));
    $name = trim((string) $this->argument('name'));
    $expiresDays = $this->option('expires-days');
    $abilities = collect((array) $this->option('ability'))
        ->map(fn (mixed $ability): string => trim((string) $ability))
        ->filter()
        ->values()
        ->all();

    $user = ctype_digit($userArgument)
        ? User::query()->find((int) $userArgument)
        : User::query()->where('email', $userArgument)->first();

    if (! $user) {
        $this->error("User not found for [{$userArgument}].");

        return Command::FAILURE;
    }

    if ($name === '') {
        $this->error('Token name must not be empty.');

        return Command::FAILURE;
    }

    if ($abilities === []) {
        $abilities = ['graphql:read-internal'];
    }

    $expiresAt = null;

    if ($expiresDays !== null && trim((string) $expiresDays) !== '') {
        $days = (int) $expiresDays;

        if ($days < 1) {
            $this->error('--expires-days must be at least 1.');

            return Command::FAILURE;
        }

        $expiresAt = now()->addDays($days);
    }

    $issued = $user->issueApiToken($name, $abilities, $expiresAt);
    /** @var ApiAccessToken $token */
    $token = $issued['access_token'];

    $this->info('API token created.');
    $this->line('Token ID: '.$token->id);
    $this->line('User: '.$user->email);
    $this->line('Abilities: '.implode(', ', $abilities));
    $this->line('Expires at: '.($token->expires_at?->toDateTimeString() ?? 'never'));
    $this->newLine();
    $this->warn('Store this token now. It will not be shown again.');
    $this->line((string) $issued['plain_text_token']);

    return Command::SUCCESS;
})->purpose('Issue an external API bearer token for a user');

Artisan::command('api-token:list {user : User ID or email}', function () {
    $userArgument = trim((string) $this->argument('user'));

    $user = ctype_digit($userArgument)
        ? User::query()->find((int) $userArgument)
        : User::query()->where('email', $userArgument)->first();

    if (! $user) {
        $this->error("User not found for [{$userArgument}].");

        return Command::FAILURE;
    }

    $rows = $user->apiAccessTokens()
        ->get()
        ->map(fn (ApiAccessToken $token): array => [
            $token->id,
            $token->name,
            implode(', ', $token->abilities ?? []),
            $token->last_used_at?->toDateTimeString() ?? '-',
            $token->expires_at?->toDateTimeString() ?? '-',
            $token->revoked_at?->toDateTimeString() ?? '-',
        ])
        ->all();

    $this->table(['ID', 'Name', 'Abilities', 'Last Used', 'Expires', 'Revoked'], $rows);

    return Command::SUCCESS;
})->purpose('List external API bearer tokens for a user');

Artisan::command('api-token:revoke {token : Token ID}', function () {
    $tokenId = (int) $this->argument('token');
    $token = ApiAccessToken::query()->find($tokenId);

    if (! $token) {
        $this->error("API token [{$tokenId}] not found.");

        return Command::FAILURE;
    }

    if ($token->revoked_at !== null) {
        $this->line("API token [{$tokenId}] is already revoked.");

        return Command::SUCCESS;
    }

    $token->revoke();

    $this->info("API token [{$tokenId}] revoked.");

    return Command::SUCCESS;
})->purpose('Revoke an external API bearer token');

Artisan::command('content:reindex-embeddings {content? : Optional Content ID or slug} {--provider=} {--model=} {--mode=incremental : incremental|full} {--sync}', function () {
    $contentArgument = $this->argument('content');
    $provider = $this->option('provider');
    $model = $this->option('model');
    $mode = strtolower(trim((string) $this->option('mode')));
    $sync = (bool) $this->option('sync');

    /** @var ContentEmbeddingReindexer $reindexer */
    $reindexer = app(ContentEmbeddingReindexer::class);

    if (is_string($contentArgument) && trim($contentArgument) !== '') {
        $argument = trim($contentArgument);

        /** @var Content|null $content */
        $content = Content::query()->where(
            ctype_digit($argument) ? 'id' : 'slug',
            ctype_digit($argument) ? (int) $argument : $argument,
        )->first();

        if (! $content) {
            $this->error("No content found for [{$argument}].");

            return Command::FAILURE;
        }

        if (! $sync) {
            app(ContentEmbeddingDispatcher::class)->dispatch(
                content: $content,
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
            );

            $this->info('Embedding reindex queued.');
            $this->line('Queued items: 1');
            $this->line('Scope: single content');
            $this->line('Content ID: '.$content->id);

            return Command::SUCCESS;
        }

        /** @var ContentEmbeddingGenerator $generator */
        $generator = app(ContentEmbeddingGenerator::class);

        try {
            $result = $generator->generateForContent(
                content: $content,
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
            );
        } catch (Throwable $exception) {
            $this->error(sprintf(
                '#%d %s -> failed: %s',
                $content->id,
                $content->slug,
                $exception->getMessage(),
            ));

            return Command::FAILURE;
        }

        $this->info('Embedding reindex completed.');
        $this->line(
            sprintf(
                '#%d %s -> chunks=%d, deleted=%d, provider=%s, model=%s',
                $content->id,
                $content->slug,
                $result['chunks'],
                $result['deleted'],
                $result['provider'],
                $result['model'],
            ),
        );

        return Command::SUCCESS;
    }

    if (! $reindexer->isSupportedMode($mode)) {
        $this->error('Invalid --mode. Allowed: incremental, full');

        return Command::FAILURE;
    }

    $targetCount = $reindexer->count($mode);

    if ($targetCount === 0) {
        $this->info('No content requires embedding reindex.');
        $this->line('Mode: '.$mode);

        return Command::SUCCESS;
    }

    if (! $sync) {
        $queued = $reindexer->dispatch(
            mode: $mode,
            provider: is_string($provider) && $provider !== '' ? $provider : null,
            model: is_string($model) && $model !== '' ? $model : null,
        );

        $this->info('Embedding reindex queued.');
        $this->line('Mode: '.$mode);
        $this->line('Queued items: '.$queued);

        return Command::SUCCESS;
    }

    $report = $reindexer->runSync(
        mode: $mode,
        provider: is_string($provider) && $provider !== '' ? $provider : null,
        model: is_string($model) && $model !== '' ? $model : null,
    );

    foreach ($report['failures'] as $failure) {
        $this->error(sprintf(
            '#%d %s -> failed: %s',
            $failure['content_id'],
            $failure['slug'],
            $failure['error'],
        ));
    }

    if ($report['failed'] > 0) {
        $this->error("Embedding reindex finished with failures. processed={$report['processed']}, failed={$report['failed']}");

        return Command::FAILURE;
    }

    $this->info("Embedding reindex completed. processed={$report['processed']}");
    $this->line('Mode: '.$mode);

    return Command::SUCCESS;
})->purpose('Reindex embeddings for one content or for all content records in incremental/full mode');

Artisan::command('stack:smoke {--json}', function (RuntimeHealthService $health): int {
    $report = $health->collect();

    if ((bool) $this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

        return $report['ok'] ? Command::SUCCESS : Command::FAILURE;
    }

    $rows = collect($report['checks'])
        ->map(fn (array $check): array => [
            $check['component'],
            strtoupper((string) $check['status']),
            (string) $check['message'],
            ($check['meta'] ?? []) !== [] ? (json_encode($check['meta'], JSON_UNESCAPED_SLASHES) ?: '{}') : '-',
        ])
        ->all();

    $this->table(['Component', 'Status', 'Message', 'Meta'], $rows);

    if (($report['alerts'] ?? []) !== []) {
        $this->newLine();
        $this->warn('Operational alerts:');

        foreach ($report['alerts'] as $alert) {
            $this->line(sprintf(
                '- [%s] %s | value=%s threshold=%s',
                strtoupper((string) $alert['severity']),
                (string) $alert['title'],
                (string) $alert['value'],
                (string) $alert['threshold'],
            ));
        }
    }

    $this->newLine();
    $this->line('Generated at: '.(string) ($report['generated_at'] ?? now()->toIso8601String()));

    if ($report['ok']) {
        $this->info('Smoke check passed.');

        return Command::SUCCESS;
    }

    $this->error('Smoke check failed.');

    return Command::FAILURE;
})->purpose('Run runtime smoke checks for DB/Redis/Horizon/Reverb/Ollama and queue alerts');

Artisan::command('stack:e2e-smoke {--json} {--keep-records} {--provider=} {--summary-model=} {--embedding-provider=} {--embedding-model=} {--prompt-version=} {--require-horizon} {--require-reverb} {--timeout=60}', function (RuntimeE2eSmokeService $smoke): int {
    $report = $smoke->run([
        'provider' => $this->option('provider'),
        'summary_model' => $this->option('summary-model'),
        'embedding_provider' => $this->option('embedding-provider'),
        'embedding_model' => $this->option('embedding-model'),
        'prompt_version' => $this->option('prompt-version'),
        'keep_records' => (bool) $this->option('keep-records'),
        'require_horizon' => (bool) $this->option('require-horizon'),
        'require_reverb' => (bool) $this->option('require-reverb'),
        'timeout_seconds' => (int) $this->option('timeout'),
    ]);

    if ((bool) $this->option('json')) {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) ?: '{}');

        return $report['ok'] ? Command::SUCCESS : Command::FAILURE;
    }

    $rows = collect($report['steps'])
        ->map(fn (array $step): array => [
            $step['name'],
            strtoupper((string) $step['status']),
            (string) $step['message'],
            ($step['meta'] ?? []) !== [] ? (json_encode($step['meta'], JSON_UNESCAPED_SLASHES) ?: '{}') : '-',
        ])
        ->all();

    $this->table(['Step', 'Status', 'Message', 'Meta'], $rows);

    if ($report['content_id'] !== null) {
        $this->newLine();
        $this->line('Retained content ID: '.$report['content_id']);
        $this->line('Slug: '.$report['slug']);
    }

    return $report['ok'] ? Command::SUCCESS : Command::FAILURE;
})->purpose('Run a live end-to-end smoke scenario through queue jobs, Ollama, pgvector search, and cleanup');
