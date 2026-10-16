<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Like;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Database\Eloquent\Model;

#[Bind(LikeRepository::class)]
#[Scoped]
interface LikeRepositoryInterface
{
    public function exists(User $user, Model $likeable): bool;

    public function create(User $user, Model $likeable): Like;

    public function delete(User $user, Model $likeable): void;

    public function count(Model $likeable): int;
}
