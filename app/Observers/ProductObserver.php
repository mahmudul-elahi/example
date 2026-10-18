<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\ResponseCacheTag;
use App\Models\Product;
use App\Traits\FlushesResponseCache;

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
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
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
