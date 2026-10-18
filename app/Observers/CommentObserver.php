<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Traits\FlushesResponseCache;

class CommentObserver
{
    use FlushesResponseCache;

    /**
     * Handle the Comment "saved" event.
     */
    public function saved(Comment $comment): void
    {
        $this->invalidate($comment);
    }

    /**
     * Handle the Comment "deleted" event.
     */
    public function deleted(Comment $comment): void
    {
        $this->invalidate($comment);
    }

    /**
     * Handle the Comment "restored" event.
     */
    public function restored(Comment $comment): void
    {
        $this->invalidate($comment);
    }

    /**
     * Handle the Comment "force deleted" event.
     */
    public function forceDeleted(Comment $comment): void
    {
        $this->invalidate($comment);
    }

    /**
     * The commentable relation is polymorphic, so clear only the caches the
     * affected model actually has: memorial comment threads plus the memorial
     * index for memorials, and the blog index (comments_count) for posts.
     */
    private function invalidate(Comment $comment): void
    {
        $commentable = $comment->commentable;

        if ($commentable instanceof PetMemorial) {
            $this->flushResponseCache(ResponseCacheTag::Comments, ResponseCacheTag::Memorials);

            return;
        }

        if ($commentable instanceof BlogPost) {
            $this->flushResponseCache(ResponseCacheTag::Blog);
        }
    }
}
