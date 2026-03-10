<?php

namespace App\GraphQL\Mutations;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ContentMutationValidator;

class CreateContentMutation
{
    public function __construct(
        private readonly ContentMutationValidator $validator,
    ) {}

    /**
     * @param  array{
     *     type: string,
     *     slug: string,
     *     title: string,
     *     body: string,
     *     locale?: string|null,
     *     status?: string|null
     * }  $args
     */
    public function __invoke(mixed $_, array $args): Content
    {
        $validated = $this->validator->validateForCreate($args);

        return Content::query()->create([
            'type' => ContentType::from($validated['type']),
            'slug' => $validated['slug'],
            'title' => $validated['title'],
            'body' => $validated['body'],
            'locale' => $validated['locale'] ?? 'en',
            'status' => isset($validated['status'])
                ? ContentStatus::from($validated['status'])
                : ContentStatus::DRAFT,
        ]);
    }
}
