<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogPost;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

#[Bind(LikeRepository::class)]
#[Scoped]
interface LikeRepositoryInterface
{
    public function create(User $user, BlogPost|PetMemorial|Product $likeable): Like;

    public function delete(User $user, BlogPost|PetMemorial|Product $likeable): void;

    public function count(BlogPost|PetMemorial|Product $likeable): int;
}
