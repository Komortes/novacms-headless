<?php

namespace App\GraphQL\Mutations;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ContentMutationValidator;
use Illuminate\Database\Eloquent\ModelNotFoundException;

class UpdateContentMutation
{
    public function __construct(
        private readonly ContentMutationValidator $validator,
    ) {
    }

    /**
     * @param  array{
     *     id: int|string,
     *     type?: string|null,
     *     slug?: string|null,
     *     title?: string|null,
     *     body?: string|null,
     *     locale?: string|null,
     *     status?: string|null
     * }  $args
     */
    public function __invoke(mixed $_, array $args): Content
    {
        $content = Content::query()->find((int) $args['id']);

        if (! $content) {
            throw (new ModelNotFoundException())->setModel(Content::class, [$args['id']]);
        }

        $validated = $this->validator->validateForUpdate($content, $args);

        if (array_key_exists('type', $validated)) {
            $validated['type'] = ContentType::from($validated['type']);
        }

        if (array_key_exists('status', $validated)) {
            $validated['status'] = ContentStatus::from($validated['status']);
        }

        unset($validated['id']);

        $content->fill($validated);
        $content->save();

        return $content->refresh();
    }
}
