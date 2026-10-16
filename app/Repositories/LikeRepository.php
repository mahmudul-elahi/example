<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Like;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class LikeRepository implements LikeRepositoryInterface
{
    public function exists(User $user, Model $likeable): bool
    {
        return $likeable->likes()->where('user_id', $user->id)->exists();
    }

    public function create(User $user, Model $likeable): Like
    {
        return $likeable->likes()->firstOrCreate(['user_id' => $user->id]);
    }

    public function delete(User $user, Model $likeable): void
    {
        $likeable->likes()->where('user_id', $user->id)->delete();
    }

    public function count(Model $likeable): int
    {
        return $likeable->likes()->count();
    }
}
