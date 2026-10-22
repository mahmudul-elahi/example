<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogPost;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use App\Repositories\LikeRepositoryInterface;

class LikeService implements LikeServiceInterface
{
    public function __construct(
        protected LikeRepositoryInterface $repository,
    ) {}

    public function like(User $user, BlogPost|PetMemorial|Product $likeable): int
    {
        $this->repository->create($user, $likeable);

        return $this->repository->count($likeable);
    }

    public function unlike(User $user, BlogPost|PetMemorial|Product $likeable): int
    {
        $this->repository->delete($user, $likeable);

        return $this->repository->count($likeable);
    }
}
