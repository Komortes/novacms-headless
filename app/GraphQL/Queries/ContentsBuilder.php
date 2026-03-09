<?php

namespace App\GraphQL\Queries;

use App\Services\GraphqlContentAccess;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ContentsBuilder
{
    public function __construct(
        private readonly GraphqlContentAccess $contentAccess,
    ) {
    }

    /**
     * @param  array{locale?: string|null, status?: string|null, type?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): Builder
    {
        return $this->contentAccess
            ->query($args, $context->user())
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }
}
