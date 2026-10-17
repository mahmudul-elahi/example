<?php

declare(strict_types=1);

namespace App\Observers;

use App\Models\Product;
use Spatie\ResponseCache\Facades\ResponseCache;

class ProductObserver
{
    /**
     * Handle the Product "saved" event.
     */
    public function saved(Product $product): void
    {
        $this->invalidate($product);
    }

    /**
     * Handle the Product "deleted" event.
     */
    public function deleted(Product $product): void
    {
        $this->invalidate($product);
    }

    /**
     * Handle the Product "restored" event.
     */
    public function restored(Product $product): void
    {
        $this->invalidate($product);
    }

    /**
     * Handle the Product "force deleted" event.
     */
    public function forceDeleted(Product $product): void
    {
        $this->invalidate($product);
    }

    /**
     * Invalidate cached shop and product responses.
     */
    private function invalidate(Product $product): void
    {
        ResponseCache::forget('/api/v1/shop');
        ResponseCache::forget("/api/v1/shop/{$product->slug}");
    }
}
