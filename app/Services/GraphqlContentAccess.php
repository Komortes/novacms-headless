<?php

namespace App\Services;

use App\Enums\ContentStatus;
use App\Enums\ContentType;
use App\Models\Content;
use Illuminate\Database\Eloquent\Builder;

class GraphqlContentAccess
{
    /**
     * @param  array{locale?: string|null, status?: string|null, type?: string|null}  $filters
     */
    public function query(array $filters, bool $allowInternalStatuses = false): Builder
    {
        $query = Content::query();
        $status = isset($filters['status']) && is_string($filters['status'])
            ? ContentStatus::tryFrom($filters['status'])
            : null;
        $type = isset($filters['type']) && is_string($filters['type'])
            ? ContentType::tryFrom($filters['type'])
            : null;
        $locale = isset($filters['locale']) && is_string($filters['locale'])
            ? trim($filters['locale'])
            : null;

        if (! $allowInternalStatuses) {
            $query->where('status', ContentStatus::PUBLISHED->value);
        } elseif ($status instanceof ContentStatus) {
            $query->where('status', $status->value);
        }

        if ($type instanceof ContentType) {
            $query->where('type', $type->value);
        }

        if ($locale !== null && $locale !== '') {
            $query->where('locale', $locale);
        }

        return $query;
    }
}
