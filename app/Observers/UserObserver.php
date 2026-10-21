<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\User;
use App\Traits\FlushesResponseCache;

class UserObserver
{
    use FlushesResponseCache;

    /**
     * Handle the User "updated" event. The public author payload is embedded
     * in every cached content index and comment thread.
     */
    public function updated(User $user): void
    {
        if ($user->wasChanged(['name', 'avatar'])) {
            $this->invalidate();
        }
    }

    /**
     * Handle the User "deleted" event. The database cascades the user's
     * content, so no model-level content observer runs for those deletions.
     */
    public function deleted(User $user): void
    {
        $this->invalidate();
    }

    /**
     * Invalidate every public response that embeds author details.
     */
    private function invalidate(): void
    {
        $this->flushResponseCache(
            ResponseCacheTag::Memorials,
            ResponseCacheTag::Comments,
            ResponseCacheTag::Blog,
            ResponseCacheTag::Shop,
        );
    }
}
