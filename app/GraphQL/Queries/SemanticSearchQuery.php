<?php

namespace App\GraphQL\Queries;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Services\SemanticSearchService;
use Nuwave\Lighthouse\Support\Contracts\GraphQLContext;

class SemanticSearchQuery
{
    public function __construct(
        private readonly SemanticSearchService $service,
    ) {
    }

    /**
     * @param  array{
     *     query: string,
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
        return $this->service->semanticSearch(
            query: (string) $args['query'],
            limit: (int) ($args['limit'] ?? 10),
            locale: $args['locale'] ?? null,
            status: $context->user() === null
                ? ContentStatus::PUBLISHED
                : (is_string($args['status'] ?? null) ? ContentStatus::tryFrom($args['status']) : null),
            type: is_string($args['type'] ?? null) ? ContentType::tryFrom($args['type']) : null,
            minScore: isset($args['min_score']) ? (float) $args['min_score'] : null,
        );
    }
}
