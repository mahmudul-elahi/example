<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogPost;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;

class LikeRepository implements LikeRepositoryInterface
{
    public function create(User $user, BlogPost|PetMemorial|Product $likeable): Like
    {
        return $likeable->likes()->firstOrCreate(['user_id' => $user->id]);
    }

    public function delete(User $user, BlogPost|PetMemorial|Product $likeable): void
    {
        // Delete through the models so the LikeObserver fires and can
        // invalidate the cached response caches that expose likes_count.
        $likeable->likes()
            ->where('user_id', $user->id)
            ->get()
            ->each
            ->delete();
    }

    public function count(BlogPost|PetMemorial|Product $likeable): int
    {
        return $likeable->likes()->count();
    }
}
