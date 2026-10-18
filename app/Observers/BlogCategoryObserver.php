<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\BlogCategory;
use App\Traits\FlushesResponseCache;

class BlogCategoryObserver
{
    use FlushesResponseCache;

    /**
     * Handle the BlogCategory "saved" event.
     */
    public function saved(BlogCategory $blogCategory): void
    {
        $this->invalidate();
    }

    /**
     * Handle the BlogCategory "deleted" event.
     */
    public function deleted(BlogCategory $blogCategory): void
    {
        $this->invalidate();
    }

    /**
     * Invalidate every cached variant of the blog index, which embeds the
     * category of each post.
     */
    private function invalidate(): void
    {
        $this->flushResponseCache(ResponseCacheTag::Blog);
    }
}
