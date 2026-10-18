<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Traits\FlushesResponseCache;

class LikeObserver
{
    use FlushesResponseCache;

    /**
     * Handle the Like "saved" event.
     */
    public function saved(Like $like): void
    {
        $this->invalidate($like);
    }

    /**
     * Handle the Like "deleted" event.
     */
    public function deleted(Like $like): void
    {
        $this->invalidate($like);
    }

    /**
     * The likeable relation is polymorphic, so clear the cache only for the
     * model whose cached lists expose likes_count (the memorial index).
     */
    private function invalidate(Like $like): void
    {
        if ($like->likeable instanceof PetMemorial) {
            $this->flushResponseCache(ResponseCacheTag::Memorials);
        }
    }
}
