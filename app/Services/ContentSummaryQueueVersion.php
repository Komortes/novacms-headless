<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;

class ContentSummaryQueueVersion
{
    private const KEY_PREFIX = 'content-summary-queue-version:';

    public function current(int $contentId): int
    {
        return (int) Cache::get($this->key($contentId), 0);
    }

    public function nextForDispatch(int $contentId): int
    {
        return (int) Cache::increment($this->key($contentId));
    }

    public function cancelPending(int $contentId): int
    {
        return (int) Cache::increment($this->key($contentId));
    }

    private function key(int $contentId): string
    {
        return self::KEY_PREFIX.$contentId;
    }
}
