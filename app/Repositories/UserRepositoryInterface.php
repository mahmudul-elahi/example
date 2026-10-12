<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;

#[Bind(UserRepository::class)]
#[Scoped]
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    public function loadRoles(User $user): User;

    public function updatePassword(User $user, string $password): void;
}
