<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\BlogCategory;
use App\Models\User;

class BlogCategoryPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, BlogCategory $category): bool
    {
        return true;
    }

    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BlogCategory $category): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, BlogCategory $category): bool
    {
        return $user->isAdmin();
    }
}
