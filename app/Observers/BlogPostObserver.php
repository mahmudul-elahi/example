<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\BlogPost;
use App\Traits\FlushesResponseCache;

class BlogPostObserver
{
    use FlushesResponseCache;

    /**
     * Handle the BlogPost "saved" event.
     */
    public function saved(BlogPost $blogPost): void
    {
        $this->invalidate();
    }

    /**
     * Handle the BlogPost "deleted" event.
     */
    public function deleted(BlogPost $blogPost): void
    {
        $this->invalidate();
    }

    /**
     * Handle the BlogPost "restored" event.
     */
    public function restored(BlogPost $blogPost): void
    {
        $this->invalidate();
    }

    /**
     * Handle the BlogPost "force deleted" event.
     */
    public function forceDeleted(BlogPost $blogPost): void
    {
        $this->invalidate();
    }

    /**
     * Invalidate every cached variant (pagination and search) of the blog index.
     */
    private function invalidate(): void
    {
        $this->flushResponseCache(ResponseCacheTag::Blog);
    }
}
