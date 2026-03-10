<?php

namespace App\GraphQL\Queries;

use App\Models\Content;
use App\Services\ApiTokenAuthenticator;
use App\Services\GraphqlContentAccess;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ContentQuery
{
    public function __construct(
        private readonly GraphqlContentAccess $contentAccess,
    ) {}

    /**
     * @param  array{id: int|string, locale?: string|null, status?: string|null, type?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): ?Content
    {
        $request = $context->request();
        $token = $request !== null ? app(ApiTokenAuthenticator::class)->currentToken($request) : null;
        $allowInternalStatuses = $context->user() !== null && ($token === null || $token->can('graphql:read-internal'));

        return $this->contentAccess
            ->query($args, $allowInternalStatuses)
            ->whereKey((int) $args['id'])
            ->first();
    }
}
