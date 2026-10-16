<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\User;
use App\Repositories\LikeRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class LikeService implements LikeServiceInterface
{
    public function __construct(
        protected LikeRepositoryInterface $repository,
    ) {}

    public function like(User $user, Model $likeable): int
    {
        $this->repository->create($user, $likeable);

        return $this->repository->count($likeable);
    }

    public function unlike(User $user, Model $likeable): int
    {
        $this->repository->delete($user, $likeable);

        return $this->repository->count($likeable);
    }
}
