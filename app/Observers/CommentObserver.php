<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Comment;
use App\Models\PetMemorial;
use Spatie\ResponseCache\Facades\ResponseCache;

class CommentObserver
{
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
     * Invalidate cached comment threads and affected memorial lists.
     */
    private function invalidate(Comment $comment): void
    {
        $commentable = $comment->commentable;

        if ($commentable instanceof PetMemorial) {
            ResponseCache::forget("/api/v1/memorials/{$commentable->slug}/comments");
            ResponseCache::forget('/api/v1/memorials');
        }
    }
}
