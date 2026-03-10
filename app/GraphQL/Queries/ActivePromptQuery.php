<?php

namespace App\GraphQL\Queries;

use App\Services\PromptRegistry;

class ActivePromptQuery
{
    public function __construct(
        private readonly PromptRegistry $registry,
    ) {}

    /**
     * @param  array{name: string, version?: string|null}  $args
     */
    public function __invoke(mixed $_, array $args): mixed
    {
        return $this->registry->findActive(
            $args['name'],
            $args['version'] ?? null,
        );
    }
}
