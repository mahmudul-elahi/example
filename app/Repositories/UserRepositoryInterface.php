<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Roles;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(UserRepository::class)]
#[Scoped]
interface UserRepositoryInterface
{
    public function findByEmail(string $email): ?User;

    public function find(int $id): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    public function delete(User $user): void;

    /**
     * @return list<string>
     */
    public function authoredMediaFiles(User $user): array;

    public function loadRoles(User $user): User;

    /**
     * @return array{memorials_count: int, likes_count: int, comments_count: int}
     */
    public function getStats(User $user): array;

    public function getProfile(User $user, ?User $viewer = null): User;

    public function updatePassword(User $user, string $password): void;

    public function countByRole(Roles $role): int;

    public function hasOtherAdmin(User $user): bool;

    public function countCreatedSince(DateTimeInterface $since): int;

    public function getUsers(int $perPage = 15, ?string $search = null, ?Roles $role = null): LengthAwarePaginator;
}
