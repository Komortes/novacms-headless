<?php

namespace App\Services;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Exceptions\AiProviderException;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\Prompt;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use Throwable;

class ContentSummaryGenerator
{
    public function __construct(
        private readonly AiProviderInterface $aiProvider,
        private readonly PromptRegistry $promptRegistry,
        private readonly ContentSummaryEventLogger $eventLogger,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     * @param  array<string, mixed>  $runContext
     */
    public function generateForContent(
        Content $content,
        ?string $promptVersion = null,
        array $options = [],
        array $runContext = [],
    ): ContentAiSummary
    {
        $summary = $content->summary()->firstOrCreate(
            ['content_id' => $content->id],
            ['status' => SummaryStatus::PENDING],
        );

        $summary->forceFill([
            'status' => SummaryStatus::GENERATING,
            'last_error' => null,
        ])->save();

        $startedAt = microtime(true);
        $queueVersion = is_numeric($runContext['queue_version'] ?? null) ? (int) $runContext['queue_version'] : null;
        $waitMs = is_numeric($runContext['wait_ms'] ?? null) ? (int) $runContext['wait_ms'] : null;
        $provider = is_string($runContext['provider'] ?? null)
            ? trim((string) $runContext['provider'])
            : (string) config('ai.provider', 'ollama');
        $selectedModel = is_string($options['model'] ?? null) && trim((string) $options['model']) !== ''
            ? trim((string) $options['model'])
            : null;

        try {
            $generated = $this->generatePayload($content, $promptVersion, $options);
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $summary->forceFill([
                'summary_tldr' => $this->normalizeNullableString(data_get($generated['payload'], 'summary_tldr')),
                'summary_bullets' => $this->normalizeStringArray(data_get($generated['payload'], 'summary_bullets')),
                'summary_meta_description' => $this->normalizeNullableString(data_get($generated['payload'], 'summary_meta_description')),
                'summary_faq' => $this->normalizeFaq(data_get($generated['payload'], 'summary_faq')),
                'summary_tags' => $this->normalizeStringArray(data_get($generated['payload'], 'summary_tags')),
                'status' => SummaryStatus::READY,
                'model' => $generated['model'],
                'prompt_version' => $generated['prompt_version'],
                'tokens_in' => $generated['tokens_in'],
                'tokens_out' => $generated['tokens_out'],
                'generation_ms' => $durationMs,
                'last_error' => null,
            ])->save();

            $this->eventLogger->record(
                content: $content,
                event: 'completed',
                summary: $summary,
                provider: $provider,
                model: $summary->model,
                queueVersion: $queueVersion,
                waitMs: $waitMs,
                durationMs: $durationMs,
                meta: [
                    'pipeline' => $generated['pipeline'],
                    'chunks' => $generated['chunks'],
                ],
            );
        } catch (Throwable $exception) {
            $durationMs = (int) round((microtime(true) - $startedAt) * 1000);

            $summary->forceFill([
                'status' => SummaryStatus::FAILED,
                'generation_ms' => $durationMs,
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            $this->eventLogger->record(
                content: $content,
                event: 'failed',
                summary: $summary,
                provider: $provider,
                model: $selectedModel ?? $summary->model,
                queueVersion: $queueVersion,
                waitMs: $waitMs,
                durationMs: $durationMs,
                message: Str::limit($exception->getMessage(), 500),
            );

            throw $exception;
        }

        return $summary->refresh();
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{
     *     payload: array<string, mixed>,
     *     model: ?string,
     *     prompt_version: ?string,
     *     tokens_in: ?int,
     *     tokens_out: ?int,
     *     pipeline: string,
     *     chunks: int
     * }
     */
    private function generatePayload(Content $content, ?string $promptVersion, array $options): array
    {
        if ($this->shouldUseMapReduce($content->body)) {
            $mapPrompt = $this->promptRegistry->findActive('content.summary.map', $promptVersion);
            $reducePrompt = $this->promptRegistry->findActive('content.summary.reduce', $promptVersion);

            if ($mapPrompt !== null && $reducePrompt !== null) {
                return $this->generatePayloadMapReduce($content, $mapPrompt, $reducePrompt, $promptVersion, $options);
            }
        }

        $prompt = $this->promptRegistry->resolveActive('content.summary', $promptVersion);
        $promptText = $this->buildPrompt($content, $prompt);
        $result = $this->aiProvider->generate(
            $promptText,
            array_merge(['format' => 'json'], $options),
        );

        return [
            'payload' => $this->decodeJsonPayload($result->text),
            'model' => $result->model,
            'prompt_version' => $prompt->version,
            'tokens_in' => $result->tokensIn,
            'tokens_out' => $result->tokensOut,
            'pipeline' => 'single',
            'chunks' => 1,
        ];
    }

    /**
     * @param  array<string, mixed>  $options
     * @return array{
     *     payload: array<string, mixed>,
     *     model: ?string,
     *     prompt_version: ?string,
     *     tokens_in: ?int,
     *     tokens_out: ?int,
     *     pipeline: string,
     *     chunks: int
     * }
     */
    private function generatePayloadMapReduce(
        Content $content,
        Prompt $mapPrompt,
        Prompt $reducePrompt,
        ?string $promptVersion,
        array $options,
    ): array
    {
        $chunks = $this->splitIntoChunks((string) $content->body);
        if (count($chunks) < 2) {
            $fallbackPrompt = $this->promptRegistry->resolveActive('content.summary', $promptVersion);
            $fallbackResult = $this->aiProvider->generate(
                $this->buildPrompt($content, $fallbackPrompt),
                array_merge(['format' => 'json'], $options),
            );

            return [
                'payload' => $this->decodeJsonPayload($fallbackResult->text),
                'model' => $fallbackResult->model,
                'prompt_version' => $fallbackPrompt->version,
                'tokens_in' => $fallbackResult->tokensIn,
                'tokens_out' => $fallbackResult->tokensOut,
                'pipeline' => 'single',
                'chunks' => 1,
            ];
        }

        $mapOutputs = [];
        $tokensIn = 0;
        $tokensOut = 0;
        $model = null;
        $totalChunks = count($chunks);

        foreach ($chunks as $index => $chunk) {
            $mapResult = $this->aiProvider->generate(
                $this->buildMapPrompt($content, $mapPrompt, $chunk, $index + 1, $totalChunks),
                array_merge(['format' => 'json'], $options),
            );

            $mapPayload = $this->decodeJsonPayload($mapResult->text);
            $mapOutputs[] = $this->normalizeMapPayload($mapPayload);
            $tokensIn += max(0, (int) ($mapResult->tokensIn ?? 0));
            $tokensOut += max(0, (int) ($mapResult->tokensOut ?? 0));
            $model = $mapResult->model ?? $model;
        }

        $reduceResult = $this->aiProvider->generate(
            $this->buildReducePrompt($content, $reducePrompt, collect($mapOutputs)),
            array_merge(['format' => 'json'], $options),
        );

        $tokensIn += max(0, (int) ($reduceResult->tokensIn ?? 0));
        $tokensOut += max(0, (int) ($reduceResult->tokensOut ?? 0));
        $model = $reduceResult->model ?? $model;

        return [
            'payload' => $this->decodeJsonPayload($reduceResult->text),
            'model' => $model,
            'prompt_version' => sprintf('mr:%s+%s', $mapPrompt->version, $reducePrompt->version),
            'tokens_in' => $tokensIn,
            'tokens_out' => $tokensOut,
            'pipeline' => 'map-reduce',
            'chunks' => $totalChunks,
        ];
    }

    private function shouldUseMapReduce(string $body): bool
    {
        if (! (bool) config('ai.map_reduce.enabled', true)) {
            return false;
        }

        $bodyLength = Str::length(trim($body));
        $minChars = max(1000, (int) config('ai.map_reduce.min_body_chars', 5000));

        return $bodyLength >= $minChars;
    }

    /**
     * @return list<string>
     */
    private function splitIntoChunks(string $markdown): array
    {
        $chunkChars = max(1200, (int) config('ai.map_reduce.chunk_chars', 3500));
        $maxChunks = max(2, (int) config('ai.map_reduce.max_chunks', 12));
        $normalized = trim(str_replace("\r\n", "\n", $markdown));

        if ($normalized === '') {
            return [];
        }

        $segments = preg_split('/\n{2,}/', $normalized) ?: [$normalized];
        $chunks = [];
        $current = '';

        foreach ($segments as $segment) {
            $segment = trim((string) $segment);
            if ($segment === '') {
                continue;
            }

            $candidate = $current === '' ? $segment : $current."\n\n".$segment;

            if (Str::length($candidate) <= $chunkChars) {
                $current = $candidate;
                continue;
            }

            if ($current !== '') {
                $chunks[] = $current;
                $current = '';
            }

            if (Str::length($segment) <= $chunkChars) {
                $current = $segment;
                continue;
            }

            foreach ($this->splitLongSegment($segment, $chunkChars) as $piece) {
                $chunks[] = $piece;

                if (count($chunks) >= $maxChunks) {
                    return $chunks;
                }
            }
        }

        if ($current !== '') {
            $chunks[] = $current;
        }

        if (count($chunks) > $maxChunks) {
            $chunks = array_slice($chunks, 0, $maxChunks);
        }

        return $chunks;
    }

    /**
     * @return list<string>
     */
    private function splitLongSegment(string $segment, int $chunkChars): array
    {
        $pieces = [];
        $length = Str::length($segment);
        $offset = 0;

        while ($offset < $length) {
            $pieces[] = trim(Str::substr($segment, $offset, $chunkChars));
            $offset += $chunkChars;
        }

        return array_values(array_filter($pieces, fn (string $piece): bool => $piece !== ''));
    }

    private function buildPrompt(Content $content, Prompt $prompt): string
    {
        return trim($prompt->template)."\n\n"
            ."Parameters:\n"
            .$this->renderPromptParameters($prompt)."\n\n"
            ."Content metadata:\n"
            ."- type: ".($content->type->value ?? $content->type)."\n"
            ."- slug: {$content->slug}\n"
            ."- locale: {$content->locale}\n"
            ."- title: {$content->title}\n\n"
            ."Markdown content:\n"
            ."```markdown\n{$content->body}\n```";
    }

    private function buildMapPrompt(Content $content, Prompt $prompt, string $chunk, int $chunkIndex, int $totalChunks): string
    {
        return trim($prompt->template)."\n\n"
            ."Parameters:\n"
            .$this->renderPromptParameters($prompt)."\n\n"
            ."Content metadata:\n"
            ."- type: ".($content->type->value ?? $content->type)."\n"
            ."- slug: {$content->slug}\n"
            ."- locale: {$content->locale}\n"
            ."- title: {$content->title}\n"
            ."- chunk: {$chunkIndex}/{$totalChunks}\n\n"
            ."Chunk markdown:\n"
            ."```markdown\n{$chunk}\n```";
    }

    /**
     * @param  Collection<int, array{key_points: list<string>, candidate_faq: list<array{question: string, answer: string}>, candidate_tags: list<string>}>  $mapOutputs
     */
    private function buildReducePrompt(Content $content, Prompt $prompt, Collection $mapOutputs): string
    {
        $mapPayload = json_encode(
            $mapOutputs->values()->all(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        ) ?: '[]';

        return trim($prompt->template)."\n\n"
            ."Parameters:\n"
            .$this->renderPromptParameters($prompt)."\n\n"
            ."Content metadata:\n"
            ."- type: ".($content->type->value ?? $content->type)."\n"
            ."- slug: {$content->slug}\n"
            ."- locale: {$content->locale}\n"
            ."- title: {$content->title}\n\n"
            ."Partial map outputs:\n"
            ."```json\n{$mapPayload}\n```";
    }

    private function renderPromptParameters(Prompt $prompt): string
    {
        $parameters = is_array($prompt->parameters) ? $prompt->parameters : [];
        $renderedParameters = json_encode(
            $parameters,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return $renderedParameters ?: '{}';
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{
     *     key_points: list<string>,
     *     candidate_faq: list<array{question: string, answer: string}>,
     *     candidate_tags: list<string>
     * }
     */
    private function normalizeMapPayload(array $payload): array
    {
        return [
            'key_points' => $this->normalizeStringArray(data_get($payload, 'key_points')),
            'candidate_faq' => $this->normalizeFaq(data_get($payload, 'candidate_faq')),
            'candidate_tags' => $this->normalizeStringArray(data_get($payload, 'candidate_tags')),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decodeJsonPayload(string $text): array
    {
        $trimmed = trim($text);

        $decoded = $this->tryDecodeJson($trimmed);
        if ($decoded !== null) {
            return $decoded;
        }

        if (preg_match('/```(?:json)?\s*(\{.*\})\s*```/is', $trimmed, $matches) === 1) {
            $decoded = $this->tryDecodeJson($matches[1]);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        $start = strpos($trimmed, '{');
        $end = strrpos($trimmed, '}');
        if ($start !== false && $end !== false && $end > $start) {
            $candidate = substr($trimmed, $start, $end - $start + 1);
            $decoded = $this->tryDecodeJson($candidate);
            if ($decoded !== null) {
                return $decoded;
            }
        }

        throw new AiProviderException('AI response is not valid JSON.');
    }

    /**
     * @return array<string, mixed>|null
     */
    private function tryDecodeJson(string $text): ?array
    {
        $decoded = json_decode($text, true);

        return is_array($decoded) ? $decoded : null;
    }

    private function normalizeNullableString(mixed $value): ?string
    {
        if (! is_scalar($value)) {
            return null;
        }

        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    /**
     * @return list<string>
     */
    private function normalizeStringArray(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (! is_scalar($item)) {
                continue;
            }

            $normalized = trim((string) $item);

            if ($normalized === '' || in_array($normalized, $result, true)) {
                continue;
            }

            $result[] = $normalized;
        }

        return $result;
    }

    /**
     * @return list<array{question: string, answer: string}>
     */
    private function normalizeFaq(mixed $value): array
    {
        if (! is_array($value)) {
            return [];
        }

        $result = [];

        foreach ($value as $item) {
            if (is_string($item)) {
                $question = trim($item);
                if ($question !== '') {
                    $result[] = ['question' => $question, 'answer' => ''];
                }
                continue;
            }

            if (! is_array($item)) {
                continue;
            }

            $question = trim((string) ($item['question'] ?? ''));
            $answer = trim((string) ($item['answer'] ?? ''));

            if ($question === '') {
                continue;
            }

            $result[] = [
                'question' => $question,
                'answer' => $answer,
            ];
        }

        return $result;
    }
}
