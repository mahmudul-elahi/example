<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

#[Bind(UserService::class)]
#[Scoped]
interface UserServiceInterface
{
    public function getUsers(int $perPage = 15, ?string $search = null, ?Roles $role = null): LengthAwarePaginator;

    public function find(int $id): User;

    public function getProfile(User $user, ?User $viewer = null): User;

    /**
     * @return array{memorials_count: int, likes_count: int, comments_count: int}
     */
    public function getStats(User $user): array;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User;

    /**
     * @param  array<string, mixed>  $data
     */
    public function updateProfile(User $user, array $data): User;

    public function updateAvatar(User $user, UploadedFile $avatar): User;

    public function changePassword(User $user, string $newPassword): void;

    public function delete(User $user, User $actor): void;
}
