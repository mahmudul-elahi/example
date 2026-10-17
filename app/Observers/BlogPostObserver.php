<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BlogPost;
use Spatie\ResponseCache\Facades\ResponseCache;

class BlogPostObserver
{
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
     * Invalidate cached blog responses.
     */
    private function invalidate(): void
    {
        ResponseCache::forget('/api/v1/blog');
    }
}
