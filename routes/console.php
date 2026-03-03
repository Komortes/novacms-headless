<?php

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ContentEmbeddingDispatcher;
use App\Services\ContentEmbeddingGenerator;
use App\Services\ContentSummaryGenerator;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Symfony\Component\Console\Command\Command;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('content:generate-summary {content : Content ID or slug} {--prompt-version=} {--provider=} {--model=}', function () {
    $provider = $this->option('provider');
    if (is_string($provider) && $provider !== '') {
        config()->set('ai.provider', $provider);
    }

    $contentArgument = (string) $this->argument('content');

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

    /** @var ContentSummaryGenerator $generator */
    $generator = app(ContentSummaryGenerator::class);
    try {
        $generationOptions = [];

        if (is_string($this->option('model')) && $this->option('model') !== '') {
            $generationOptions['model'] = (string) $this->option('model');
        }

        $summary = $generator->generateForContent(
            $content,
            $this->option('prompt-version') ?: null,
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
})->purpose('Generate content AI summary synchronously using configured AI provider');

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

Artisan::command('content:reindex-embeddings {content? : Optional Content ID or slug} {--provider=} {--model=} {--sync}', function () {
    $contentArgument = $this->argument('content');
    $provider = $this->option('provider');
    $model = $this->option('model');
    $sync = (bool) $this->option('sync');

    $query = Content::query()->orderBy('id');

    if (is_string($contentArgument) && trim($contentArgument) !== '') {
        $argument = trim($contentArgument);
        $query->where(
            ctype_digit($argument) ? 'id' : 'slug',
            ctype_digit($argument) ? (int) $argument : $argument,
        );
    }

    $contents = $query->get();

    if ($contents->isEmpty()) {
        $target = is_string($contentArgument) && trim($contentArgument) !== '' ? $contentArgument : 'all';
        $this->error("No content found for [{$target}].");

        return Command::FAILURE;
    }

    if (! $sync) {
        /** @var ContentEmbeddingDispatcher $dispatcher */
        $dispatcher = app(ContentEmbeddingDispatcher::class);

        foreach ($contents as $content) {
            $dispatcher->dispatch(
                content: $content,
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
            );
        }

        $this->info('Embedding reindex queued.');
        $this->line('Queued items: '.$contents->count());

        return Command::SUCCESS;
    }

    /** @var ContentEmbeddingGenerator $generator */
    $generator = app(ContentEmbeddingGenerator::class);
    $processed = 0;
    $failed = 0;

    foreach ($contents as $content) {
        try {
            $result = $generator->generateForContent(
                content: $content,
                provider: is_string($provider) && $provider !== '' ? $provider : null,
                model: is_string($model) && $model !== '' ? $model : null,
            );
            $processed++;

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
        } catch (Throwable $exception) {
            $failed++;
            $this->error(sprintf(
                '#%d %s -> failed: %s',
                $content->id,
                $content->slug,
                $exception->getMessage(),
            ));
        }
    }

    if ($failed > 0) {
        $this->error("Embedding reindex finished with failures. processed={$processed}, failed={$failed}");

        return Command::FAILURE;
    }

    $this->info("Embedding reindex completed. processed={$processed}");

    return Command::SUCCESS;
})->purpose('Reindex embeddings for one content or all content records');
