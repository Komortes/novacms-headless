<?php

namespace App\GraphQL\Queries;

use App\Services\SemanticSearchService;

class RelatedContentQuery
{
    public function __construct(
        private readonly SemanticSearchService $service,
    ) {
    }

    /**
     * @param  array{content_id: int|string, limit?: int|null}  $args
     * @return list<array{content: \App\Models\Content, score: float}>
     */
    public function __invoke(mixed $_, array $args): array
    {
        return $this->service->relatedContent(
            contentId: (int) $args['content_id'],
            limit: (int) ($args['limit'] ?? 5),
        );
    }
}
