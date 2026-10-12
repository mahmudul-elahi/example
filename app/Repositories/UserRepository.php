<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\User;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->with('roles')
            ->where('email', $email)
            ->first();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    public function loadRoles(User $user): User
    {
        return $user->load('roles');
    }

    public function updatePassword(User $user, string $password): void
    {
        // The User model's "hashed" cast keeps the plain value hashed on save.
        $user->update(['password' => $password]);
    }
}
