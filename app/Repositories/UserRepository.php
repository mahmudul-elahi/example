<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\Roles;
use App\Models\User;
use DateTimeInterface;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserRepository implements UserRepositoryInterface
{
    public function findByEmail(string $email): ?User
    {
        return User::query()
            ->with('roles')
            ->where('email', $email)
            ->first();
    }

    public function find(int $id): User
    {
        return User::query()
            ->with('roles')
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): User
    {
        return User::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(User $user, array $data): User
    {
        $user->update($data);

        return $user;
    }

    public function delete(User $user): void
    {
        $user->delete();
    }

    /**
     * The cover images belonging to content the user authored, including
     * soft-deleted content that a user deletion would remove from the database.
     *
     * @return list<string>
     */
    public function contentCoverImages(User $user): array
    {
        $images = [
            ...$user->blogPosts()->withTrashed()->whereNotNull('cover_image')->pluck('cover_image')->all(),
            ...$user->petMemorials()->withTrashed()->whereNotNull('cover_image')->pluck('cover_image')->all(),
            ...$user->products()->withTrashed()->whereNotNull('cover_image')->pluck('cover_image')->all(),
        ];

        return array_values(array_unique($images));
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

    public function countByRole(Roles $role): int
    {
        return User::query()->role($role->value)->count();
    }

    public function hasOtherAdmin(User $user): bool
    {
        return User::query()
            ->role(Roles::Admin->value)
            ->whereKeyNot($user->id)
            ->exists();
    }

    public function countCreatedSince(DateTimeInterface $since): int
    {
        return User::query()->where('created_at', '>=', $since)->count();
    }

    public function getUsers(int $perPage = 15, ?string $search = null, ?Roles $role = null): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return User::query()
            ->with('roles')
            ->when($search, function ($query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->when($role, function ($query, Roles $role): void {
                $query->role($role->value);
            })
            ->latest('id')
            ->paginate($perPage);
    }
}
