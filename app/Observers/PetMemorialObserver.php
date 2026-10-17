<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\PetMemorial;
use Spatie\ResponseCache\Facades\ResponseCache;

class PetMemorialObserver
{
    /**
     * Handle the PetMemorial "saved" event.
     */
    public function saved(PetMemorial $memorial): void
    {
        $this->invalidate($memorial);
    }

    /**
     * Handle the PetMemorial "deleted" event.
     */
    public function deleted(PetMemorial $memorial): void
    {
        $this->invalidate($memorial);
    }

    /**
     * Handle the PetMemorial "restored" event.
     */
    public function restored(PetMemorial $memorial): void
    {
        $this->invalidate($memorial);
    }

    /**
     * Handle the PetMemorial "force deleted" event.
     */
    public function forceDeleted(PetMemorial $memorial): void
    {
        $this->invalidate($memorial);
    }

    /**
     * Invalidate cached memorial responses.
     */
    private function invalidate(PetMemorial $memorial): void
    {
        ResponseCache::forget('/api/v1/memorials');
        ResponseCache::forget("/api/v1/memorials/{$memorial->slug}/comments");
    }
}
