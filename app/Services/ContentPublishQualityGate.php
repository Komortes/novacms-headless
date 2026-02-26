<?php

namespace App\Services;

use App\Enums\SummaryStatus;
use App\Models\Content;
use Illuminate\Validation\ValidationException;

class ContentPublishQualityGate
{
    public function assertCanPublish(Content $content): void
    {
        $errors = $this->errorsFor($content);

        if ($errors === []) {
            return;
        }

        throw ValidationException::withMessages($errors);
    }

    /**
     * @return array<string, list<string>>
     */
    public function errorsFor(Content $content): array
    {
        $summary = $content->summary()->first();
        $errors = [];

        if (! $summary || $summary->status !== SummaryStatus::READY) {
            $errors['status'] = ['Publish is blocked: AI summary must be in "ready" state.'];

            return $errors;
        }

        if (mb_strlen(trim((string) ($summary->summary_tldr ?? ''))) < 20) {
            $errors['summary_tldr'] = ['TL;DR is too short. Regenerate summary before publish.'];
        }

        if (count((array) $summary->summary_bullets) < 2) {
            $errors['summary_bullets'] = ['At least 2 summary bullets are required before publish.'];
        }

        $metaDescription = trim((string) ($summary->summary_meta_description ?? ''));
        if ($metaDescription === '' || mb_strlen($metaDescription) > 155) {
            $errors['summary_meta_description'] = ['Meta description is required and must be <= 155 chars.'];
        }

        $faq = array_filter(
            (array) $summary->summary_faq,
            fn (mixed $item): bool => is_array($item)
                && trim((string) ($item['question'] ?? '')) !== ''
                && trim((string) ($item['answer'] ?? '')) !== '',
        );
        if (count($faq) < 1) {
            $errors['summary_faq'] = ['At least one FAQ pair with question and answer is required.'];
        }

        if (count((array) $summary->summary_tags) < 2) {
            $errors['summary_tags'] = ['At least 2 tags are required before publish.'];
        }

        return $errors;
    }
}
