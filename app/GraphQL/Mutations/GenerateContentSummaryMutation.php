<?php

namespace App\GraphQL\Mutations;

use App\Enums\SummaryStatus;
use App\Models\Content;
use App\Services\ContentSummaryDispatcher;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class GenerateContentSummaryMutation
{
    public function __construct(
        private readonly ContentSummaryDispatcher $dispatcher,
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

        $this->dispatcher->dispatch(
            content: $content,
            promptVersion: $args['prompt_version'] ?? null,
        );

        return $content->summary()->firstOrCreate(
            ['content_id' => $content->id],
            ['status' => SummaryStatus::PENDING],
        );
    }
}
