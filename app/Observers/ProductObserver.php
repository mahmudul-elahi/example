<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\Product;
use App\Traits\FlushesResponseCache;
use Illuminate\Support\Facades\Storage;

class ProductObserver
{
    use FlushesResponseCache;

    /**
     * Handle the Product "saved" event.
     */
    public function saved(Product $product): void
    {
        $this->invalidate();
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->invalidate();
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        $this->invalidate();
    }

    /**
     * Handle the Product "force deleted" event. The record is gone for good,
     * so its cover image goes with it. Soft deletes keep the file so a restore
     * cannot end up with a broken image URL.
     */
    public function forceDeleted(Product $product): void
    {
        if ($product->cover_image !== null) {
            Storage::delete($product->cover_image);
        }

        $this->invalidate();
    }

    /**
     * Invalidate every cached variant of the shop index and product pages.
     */
    private function invalidate(): void
    {
        $this->flushResponseCache(ResponseCacheTag::Shop);
    }
}
