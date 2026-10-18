<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\PetMemorial;
use App\Traits\FlushesResponseCache;
use Illuminate\Support\Facades\Storage;

class PetMemorialObserver
{
    use FlushesResponseCache;

    /**
     * Handle the PetMemorial "saved" event.
     */
    public function saved(PetMemorial $memorial): void
    {
        $this->invalidate();
    }

    /**
     * Handle the PetMemorial "deleted" event.
     */
    public function deleted(PetMemorial $memorial): void
    {
        $this->invalidate();
    }

    /**
     * Handle the PetMemorial "restored" event.
     */
    public function restored(PetMemorial $memorial): void
    {
        $this->invalidate();
    }

    /**
     * Handle the PetMemorial "force deleted" event. The record is gone for
     * good, so its cover image goes with it. Soft deletes keep the file so a
     * restore cannot end up with a broken image URL.
     */
    public function forceDeleted(PetMemorial $memorial): void
    {
        if ($memorial->cover_image !== null) {
            Storage::delete($memorial->cover_image);
        }

        $this->invalidate();
    }

    /**
     * Invalidate every cached variant of the memorial index and the comment
     * threads, which the tags group together.
     */
    private function invalidate(): void
    {
        $this->flushResponseCache(ResponseCacheTag::Memorials, ResponseCacheTag::Comments);
    }
}
