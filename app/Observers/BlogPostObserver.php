<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\BlogPost;
use App\Traits\FlushesResponseCache;
use Illuminate\Support\Facades\Storage;

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
     * Handle the BlogPost "force deleted" event. The record is gone for good,
     * so its cover image goes with it. Soft deletes keep the file so a restore
     * cannot end up with a broken image URL.
     */
    public function forceDeleted(BlogPost $blogPost): void
    {
        if ($blogPost->cover_image !== null) {
            Storage::delete($blogPost->cover_image);
        }

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
