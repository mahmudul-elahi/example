<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\BlogCategory;
use Spatie\ResponseCache\Facades\ResponseCache;

class BlogCategoryObserver
{
    /**
     * Handle the BlogCategory "saved" event.
     */
    public function saved(BlogCategory $blogCategory): void
    {
        $this->invalidate($blogCategory);
    }

    /**
     * Handle the BlogCategory "deleted" event.
     */
    public function deleted(BlogCategory $blogCategory): void
    {
        $this->invalidate($blogCategory);
    }

    /**
     * Invalidate every cached variant of the blog index, which embeds the
     * category of each post.
     */
    private function invalidate(BlogCategory $blogCategory): void
    {
        ResponseCache::clear(['blog']);
    }
}
