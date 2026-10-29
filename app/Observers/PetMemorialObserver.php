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
     * Handle the PetMemorial "force deleting" event. Clean up image files
     * before database cascades remove the child records.
     */
    public function forceDeleting(PetMemorial $memorial): void
    {
        foreach ($memorial->images as $image) {
            Storage::delete($image->path);
        }
    }

    /**
     * Handle the PetMemorial "force deleted" event.
     */
    public function forceDeleted(PetMemorial $memorial): void
    {
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
