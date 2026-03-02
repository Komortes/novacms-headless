<?php

namespace App\GraphQL\Queries;

use App\Services\SemanticSearchService;

class SemanticSearchQuery
{
    public function __construct(
        private readonly SemanticSearchService $service,
    ) {
    }

    /**
     * @param  array{query: string, limit?: int|null, locale?: string|null}  $args
     * @return list<array{content: \App\Models\Content, score: float}>
     */
    public function __invoke(mixed $_, array $args): array
    {
        return $this->service->semanticSearch(
            query: (string) $args['query'],
            limit: (int) ($args['limit'] ?? 10),
            locale: $args['locale'] ?? null,
        );
    }
}
