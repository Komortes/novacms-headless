<?php

namespace App\Services;

use App\AI\Contracts\AiProviderInterface;
use App\AI\Exceptions\AiProviderException;
use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Models\ContentAiSummary;
use App\Models\Prompt;
use Illuminate\Support\Str;
use Throwable;

class ContentSummaryGenerator
{
    public function __construct(
        private readonly AiProviderInterface $aiProvider,
        private readonly PromptRegistry $promptRegistry,
    ) {
    }

    /**
     * @param  array<string, mixed>  $options
     */
    public function generateForContent(Content $content, ?string $promptVersion = null, array $options = []): ContentAiSummary
    {
        $summary = $content->summary()->firstOrCreate(
            ['content_id' => $content->id],
            ['status' => SummaryStatus::PENDING],
        );

        $summary->forceFill([
            'status' => SummaryStatus::GENERATING,
            'last_error' => null,
        ])->save();

        try {
            $prompt = $this->promptRegistry->resolveActive('content.summary', $promptVersion);
            $promptText = $this->buildPrompt($content, $prompt);
            $result = $this->aiProvider->generate(
                $promptText,
                array_merge(['format' => 'json'], $options),
            );

            $payload = $this->decodeJsonPayload($result->text);

            $summary->forceFill([
                'summary_tldr' => $this->normalizeNullableString(data_get($payload, 'summary_tldr')),
                'summary_bullets' => $this->normalizeStringArray(data_get($payload, 'summary_bullets')),
                'summary_meta_description' => $this->normalizeNullableString(data_get($payload, 'summary_meta_description')),
                'summary_faq' => $this->normalizeFaq(data_get($payload, 'summary_faq')),
                'summary_tags' => $this->normalizeStringArray(data_get($payload, 'summary_tags')),
                'status' => SummaryStatus::READY,
                'model' => $result->model,
                'prompt_version' => $prompt->version,
                'tokens_in' => $result->tokensIn,
                'tokens_out' => $result->tokensOut,
                'last_error' => null,
            ])->save();
        } catch (Throwable $exception) {
            $summary->forceFill([
                'status' => SummaryStatus::FAILED,
                'last_error' => Str::limit($exception->getMessage(), 2000),
            ])->save();

            throw $exception;
        }

        return $summary->refresh();
    }

    private function buildPrompt(Content $content, Prompt $prompt): string
    {
        $parameters = is_array($prompt->parameters) ? $prompt->parameters : [];
        $renderedParameters = json_encode(
            $parameters,
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE,
        );

        return trim($prompt->template)."\n\n"
            ."Parameters:\n"
            .($renderedParameters ?: "{}")."\n\n"
            ."Content metadata:\n"
            ."- type: ".($content->type->value ?? $content->type)."\n"
            ."- slug: {$content->slug}\n"
            ."- locale: {$content->locale}\n"
            ."- title: {$content->title}\n\n"
            ."Markdown content:\n"
            ."```markdown\n{$content->body}\n```";
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

