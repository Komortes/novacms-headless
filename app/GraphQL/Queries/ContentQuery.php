<?php

namespace App\GraphQL\Queries;

use App\Models\Content;
use App\Services\GraphqlContentAccess;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ContentQuery
{
    public function __construct(
        private readonly GraphqlContentAccess $contentAccess,
    ) {
    }

    /**
     * @param  array{id: int|string, locale?: string|null, status?: string|null, type?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): ?Content
    {
        return $this->contentAccess
            ->query($args, $context->user())
            ->whereKey((int) $args['id'])
            ->first();
    }
}
