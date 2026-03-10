<?php

namespace App\GraphQL\Queries;

use App\Services\ApiTokenAuthenticator;
use App\Services\GraphqlContentAccess;
use Illuminate\Database\Eloquent\Builder;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class ContentsBuilder
{
    public function __construct(
        private readonly GraphqlContentAccess $contentAccess,
    ) {}

    /**
     * @param  array{locale?: string|null, status?: string|null, type?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): Builder
    {
        $request = $context->request();
        $token = $request !== null ? app(ApiTokenAuthenticator::class)->currentToken($request) : null;
        $allowInternalStatuses = $context->user() !== null && ($token === null || $token->can('graphql:read-internal'));

        return $this->contentAccess
            ->query($args, $allowInternalStatuses)
            ->orderByDesc('updated_at')
            ->orderByDesc('id');
    }
}
