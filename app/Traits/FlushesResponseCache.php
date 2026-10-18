<?php

declare(strict_types=1);

namespace App\Traits;

use App\Enums\ResponseCacheTag;
use Spatie\ResponseCache\Facades\ResponseCache;

trait FlushesResponseCache
{
    /**
     * Invalidate every cached response carrying any of the given tags, covering
     * all of its variants (pagination, search, and per-user isolation).
     *
     * This is the single place the response cache is flushed from, so observers
     * only declare which tags their model affects.
     */
    protected function flushResponseCache(ResponseCacheTag ...$tags): void
    {
        ResponseCache::clear(ResponseCacheTag::values(...$tags));
    }
}
