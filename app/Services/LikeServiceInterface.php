<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Model;

#[Bind(LikeService::class)]
#[Scoped]
interface LikeServiceInterface
{
    /**
     * Like the model on behalf of the user and return the resulting like count.
     */
    public function like(User $user, Model $likeable): int;

    /**
     * Remove the user's like from the model and return the resulting like count.
     */
    public function unlike(User $user, Model $likeable): int;
}
