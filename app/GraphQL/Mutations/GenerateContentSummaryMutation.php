<?php

namespace App\GraphQL\Mutations;

use App\Models\Content;
use App\Services\ContentSummaryGenerator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenerateContentSummaryMutation
{
    public function __construct(
        private readonly ContentSummaryGenerator $generator,
    ) {
    }

    /**
     * @param  array{content_id: int|string, prompt_version?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args): mixed
    {
        $content = Content::query()->find($args['content_id']);

        if (! $content) {
            throw (new ModelNotFoundException())->setModel(Content::class, [$args['content_id']]);
        }

        return $this->generator->generateForContent(
            $content,
            $args['prompt_version'] ?? null,
        );
    }
}

