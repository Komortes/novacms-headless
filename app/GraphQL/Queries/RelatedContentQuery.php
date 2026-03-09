<?php

namespace App\GraphQL\Queries;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use App\Services\ApiTokenAuthenticator;
use App\Services\SemanticSearchService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class RelatedContentQuery
{
    public function __construct(
        private readonly SemanticSearchService $service,
    ) {
    }

    /**
     * @param  array{
     *     content_id: int|string,
     *     limit?: int|null,
     *     locale?: string|null,
     *     status?: string|null,
     *     type?: string|null,
     *     min_score?: float|int|null
     * }  $args
     * @return list<array{content: \App\Models\Content, score: float}>
     */
    public function __invoke(mixed $_, array $args, GraphQLContext $context): array
    {
        $request = $context->request();
        $token = $request !== null ? app(ApiTokenAuthenticator::class)->currentToken($request) : null;
        $allowInternalStatuses = $context->user() !== null && ($token === null || $token->can('graphql:read-internal'));

        if (
            ! $allowInternalStatuses
            && ! Content::query()
                ->whereKey((int) $args['content_id'])
                ->where('status', ContentStatus::PUBLISHED->value)
                ->exists()
        ) {
            return [];
        }

        return $this->service->relatedContent(
            contentId: (int) $args['content_id'],
            limit: (int) ($args['limit'] ?? 5),
            locale: $args['locale'] ?? null,
            status: ! $allowInternalStatuses
                ? ContentStatus::PUBLISHED
                : (is_string($args['status'] ?? null) ? ContentStatus::tryFrom($args['status']) : null),
            type: is_string($args['type'] ?? null) ? ContentType::tryFrom($args['type']) : null,
            minScore: isset($args['min_score']) ? (float) $args['min_score'] : null,
        );
    }
}
