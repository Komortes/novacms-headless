<?php

namespace App\Services;

use App\DomainEvents;
use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Enums\SummaryStatus;
use App\Models\Content;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class ContentCatalogManager
{
    public function __construct(
        private readonly ContentMutationValidator $contentValidator,
        private readonly ContentPublishQualityGate $publishQualityGate,
        private readonly ContentSummaryDispatcher $summaryDispatcher,
        private readonly ContentEmbeddingDispatcher $embeddingDispatcher,
        private readonly DomainEventPublisher $domainEventPublisher,
    ) {}

    /**
     * @return array{
     *     exported_at: string,
     *     count: int,
     *     contents: list<array{
     *         type: string,
     *         slug: string,
     *         title: string,
     *         body: string,
     *         locale: string,
     *         status: string,
     *         summary?: array<string, mixed>|null
     *     }>
     * }
     */
    public function export(?string $type = null, ?string $status = null, ?string $locale = null, bool $withSummaries = true): array
    {
        $query = Content::query()
            ->with($withSummaries ? ['summary'] : [])
            ->orderBy('type')
            ->orderBy('locale')
            ->orderBy('slug');

        if (filled($type)) {
            $query->where('type', trim((string) $type));
        }

        if (filled($status)) {
            $query->where('status', trim((string) $status));
        }

        if (filled($locale)) {
            $query->where('locale', trim((string) $locale));
        }

        $items = $query->get()
            ->map(function (Content $content) use ($withSummaries): array {
                $item = [
                    'type' => (string) ($content->type->value ?? $content->type),
                    'slug' => (string) $content->slug,
                    'title' => (string) $content->title,
                    'body' => (string) $content->body,
                    'locale' => (string) $content->locale,
                    'status' => (string) ($content->status->value ?? $content->status),
                ];

                if ($withSummaries) {
                    $item['summary'] = $content->summary ? [
                        'status' => (string) ($content->summary->status->value ?? $content->summary->status),
                        'summary_tldr' => $content->summary->summary_tldr,
                        'summary_bullets' => is_array($content->summary->summary_bullets) ? $content->summary->summary_bullets : [],
                        'summary_meta_description' => $content->summary->summary_meta_description,
                        'summary_faq' => is_array($content->summary->summary_faq) ? $content->summary->summary_faq : [],
                        'summary_tags' => is_array($content->summary->summary_tags) ? $content->summary->summary_tags : [],
                        'model' => $content->summary->model,
                        'prompt_version' => $content->summary->prompt_version,
                        'tokens_in' => $content->summary->tokens_in,
                        'tokens_out' => $content->summary->tokens_out,
                        'generation_ms' => $content->summary->generation_ms,
                        'last_error' => $content->summary->last_error,
                    ] : null;
                }

                return $item;
            })
            ->values()
            ->all();

        return [
            'exported_at' => now()->toIso8601String(),
            'count' => count($items),
            'contents' => $items,
        ];
    }

    /**
     * @return array{imported: int, created: int, updated: int, summaries: int, skipped: int}
     */
    public function importFromJson(string $payload, bool $upsert = true): array
    {
        $decoded = json_decode($payload, true);

        if (! is_array($decoded)) {
            throw new InvalidArgumentException('Invalid JSON payload.');
        }

        $items = $this->normalizeImportItems($decoded);

        if ($items->isEmpty()) {
            throw new InvalidArgumentException('No content records found in payload.');
        }

        $postCommitEffects = [];

        $result = DB::transaction(function () use ($items, $upsert, &$postCommitEffects): array {
            $imported = 0;
            $created = 0;
            $updated = 0;
            $summaries = 0;
            $skipped = 0;

            foreach ($items as $item) {
                $existing = Content::query()
                    ->where('slug', $item['slug'])
                    ->where('locale', $item['locale'])
                    ->first();

                if ($existing && ! $upsert) {
                    $skipped++;

                    continue;
                }

                $content = $existing ?? new Content;
                $originalHash = $existing?->content_hash;
                $targetStatus = ContentStatus::from($item['status']);
                $contentInput = Arr::except($item, ['status', 'summary']);
                $validated = $existing
                    ? $this->contentValidator->validateForUpdate($content, $contentInput)
                    : $this->contentValidator->validateForCreate($contentInput);

                $content->fill([
                    ...$validated,
                    'status' => $targetStatus === ContentStatus::PUBLISHED
                        ? ContentStatus::DRAFT
                        : $targetStatus,
                ]);
                $content->content_hash = $content->generateContentHash();

                Content::withoutEvents(fn () => $content->save());

                if ($existing) {
                    $updated++;
                } else {
                    $created++;
                }

                $contentChanged = $originalHash !== null && $originalHash !== $content->content_hash;

                if ($contentChanged) {
                    $content->embeddings()->delete();
                }

                if (! $existing || $contentChanged) {
                    $this->invalidateSummary($content);
                }

                if (is_array($item['summary'] ?? null)) {
                    $summary = $item['summary'];

                    $content->summary()->updateOrCreate(
                        ['content_id' => $content->id],
                        [
                            'status' => $summary['status'],
                            'summary_tldr' => $summary['summary_tldr'],
                            'summary_bullets' => $summary['summary_bullets'],
                            'summary_meta_description' => $summary['summary_meta_description'],
                            'summary_faq' => $summary['summary_faq'],
                            'summary_tags' => $summary['summary_tags'],
                            'model' => $summary['model'],
                            'prompt_version' => $summary['prompt_version'],
                            'tokens_in' => $summary['tokens_in'],
                            'tokens_out' => $summary['tokens_out'],
                            'generation_ms' => $summary['generation_ms'],
                            'last_error' => $summary['last_error'],
                        ],
                    );

                    $summaries++;
                }

                if ($targetStatus === ContentStatus::PUBLISHED) {
                    $this->publishQualityGate->assertCanPublish($content);
                    $content->forceFill(['status' => ContentStatus::PUBLISHED]);
                    Content::withoutEvents(fn () => $content->save());
                }

                $postCommitEffects[] = [
                    'content_id' => $content->id,
                    'generate_summary' => (! $existing || $contentChanged) && ! is_array($item['summary'] ?? null),
                    'generate_embeddings' => ! $existing || $contentChanged,
                ];

                $imported++;
            }

            return [
                'imported' => $imported,
                'created' => $created,
                'updated' => $updated,
                'summaries' => $summaries,
                'skipped' => $skipped,
            ];
        });

        $this->runPostCommitEffects($postCommitEffects);

        return $result;
    }

    /**
     * @param  array<int|string, mixed>  $decoded
     * @return Collection<int, array{
     *     type: string,
     *     slug: string,
     *     title: string,
     *     body: string,
     *     locale: string,
     *     status: string,
     *     summary: array<string, mixed>|null
     * }>
     */
    private function normalizeImportItems(array $decoded): Collection
    {
        if (array_key_exists('contents', $decoded) && is_array($decoded['contents'])) {
            $rawItems = $decoded['contents'];
        } elseif ($this->looksLikeContentItem($decoded)) {
            $rawItems = [$decoded];
        } else {
            $rawItems = $decoded;
        }

        if (! is_array($rawItems)) {
            return collect();
        }

        return collect($rawItems)
            ->map(function (mixed $item, int|string $index): array {
                if (! is_array($item) || ! $this->looksLikeContentItem($item)) {
                    throw new InvalidArgumentException("Invalid content item at index [{$index}].");
                }

                $type = ContentType::tryFrom(trim((string) $item['type']));
                $status = ContentStatus::tryFrom(trim((string) ($item['status'] ?? ContentStatus::DRAFT->value)));

                if (! $type) {
                    throw new InvalidArgumentException("Invalid content type at index [{$index}].");
                }

                if (! $status) {
                    throw new InvalidArgumentException("Invalid content status at index [{$index}].");
                }

                $summary = null;

                if (array_key_exists('summary', $item) && $item['summary'] !== null) {
                    if (! is_array($item['summary'])) {
                        throw new InvalidArgumentException("Invalid summary payload at index [{$index}].");
                    }

                    $summary = $this->normalizeSummary($item['summary'], $index);
                }

                return [
                    'type' => $type->value,
                    'slug' => trim((string) $item['slug']),
                    'title' => trim((string) $item['title']),
                    'body' => (string) $item['body'],
                    'locale' => trim((string) $item['locale']),
                    'status' => $status->value,
                    'summary' => $summary,
                ];
            })
            ->values();
    }

    /**
     * @param  array<string, mixed>  $summary
     * @return array<string, mixed>
     */
    private function normalizeSummary(array $summary, int|string $index): array
    {
        $status = SummaryStatus::tryFrom(trim((string) ($summary['status'] ?? SummaryStatus::PENDING->value)));

        if (! $status) {
            throw new InvalidArgumentException("Invalid summary status at index [{$index}].");
        }

        return [
            'status' => $status->value,
            'summary_tldr' => isset($summary['summary_tldr']) ? (string) $summary['summary_tldr'] : null,
            'summary_bullets' => is_array($summary['summary_bullets'] ?? null) ? array_values($summary['summary_bullets']) : null,
            'summary_meta_description' => isset($summary['summary_meta_description']) ? (string) $summary['summary_meta_description'] : null,
            'summary_faq' => is_array($summary['summary_faq'] ?? null) ? array_values($summary['summary_faq']) : null,
            'summary_tags' => is_array($summary['summary_tags'] ?? null) ? array_values($summary['summary_tags']) : null,
            'model' => filled($summary['model'] ?? null) ? trim((string) $summary['model']) : null,
            'prompt_version' => filled($summary['prompt_version'] ?? null) ? trim((string) $summary['prompt_version']) : null,
            'tokens_in' => is_numeric($summary['tokens_in'] ?? null) ? (int) $summary['tokens_in'] : null,
            'tokens_out' => is_numeric($summary['tokens_out'] ?? null) ? (int) $summary['tokens_out'] : null,
            'generation_ms' => is_numeric($summary['generation_ms'] ?? null) ? (int) $summary['generation_ms'] : null,
            'last_error' => isset($summary['last_error']) ? (string) $summary['last_error'] : null,
        ];
    }

    /**
     * @param  array<string, mixed>  $item
     */
    private function looksLikeContentItem(array $item): bool
    {
        return isset($item['type'], $item['slug'], $item['title'], $item['body'], $item['locale'])
            && is_scalar($item['type'])
            && is_scalar($item['slug'])
            && is_scalar($item['title'])
            && is_scalar($item['body'])
            && is_scalar($item['locale']);
    }

    private function invalidateSummary(Content $content): void
    {
        $content->summary()->updateOrCreate(
            ['content_id' => $content->id],
            [
                'summary_tldr' => null,
                'summary_bullets' => null,
                'summary_meta_description' => null,
                'summary_faq' => null,
                'summary_tags' => null,
                'status' => SummaryStatus::PENDING,
                'model' => null,
                'prompt_version' => null,
                'tokens_in' => null,
                'tokens_out' => null,
                'generation_ms' => null,
                'last_error' => null,
            ],
        );
    }

    /**
     * @param  list<array{content_id: int, generate_summary: bool, generate_embeddings: bool}>  $effects
     */
    private function runPostCommitEffects(array $effects): void
    {
        foreach ($effects as $effect) {
            $content = Content::query()->find($effect['content_id']);

            if (! $content) {
                continue;
            }

            if ($effect['generate_summary'] && (bool) config('ai.summary.auto_dispatch', true)) {
                $this->summaryDispatcher->dispatch($content);
            }

            if ($effect['generate_embeddings'] && (bool) config('ai.embeddings.auto_dispatch', true)) {
                $this->embeddingDispatcher->dispatch($content);
            }

            $this->domainEventPublisher->publish(DomainEvents::CONTENT_UPDATED, [
                'content_id' => $content->id,
                'slug' => $content->slug,
                'locale' => $content->locale,
                'status' => $content->status instanceof ContentStatus ? $content->status->value : (string) $content->status,
                'content_hash' => $content->content_hash,
            ]);
        }
    }
}
