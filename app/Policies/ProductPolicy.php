<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\ProductStatus;
use App\Models\Product;
use App\Models\User;

class ProductPolicy
{
    /**
     * Anyone may browse the shop listing.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * A product is visible when published, or to admins.
     */
    public function view(?User $user, Product $product): bool
    {
        if ($user?->isAdmin() === true) {
            return true;
        }

        return $product->status === ProductStatus::Published;
    }

    /**
     * Only admins manage the shop catalogue.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, Product $product): bool
    {
        return $user->isAdmin();
    }
}
