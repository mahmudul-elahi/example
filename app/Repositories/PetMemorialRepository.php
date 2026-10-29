<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class PetMemorialRepository implements PetMemorialRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, PetMemorial>
     */
    public function paginate(int $perPage, ?string $search, bool $publicOnly): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return PetMemorial::query()
            ->with(['user', 'images'])
            ->withCount(['comments', 'likes'])
            ->when($publicOnly, fn (Builder $query): Builder => $query->publiclyVisible())
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('pet_name', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return CursorPaginator<int, PetMemorial>
     */
    public function cursorPaginate(int $perPage, ?string $search, bool $publicOnly, ?string $cursor = null): CursorPaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return PetMemorial::query()
            ->with(['user', 'images'])
            ->withCount(['comments', 'likes'])
            ->when($publicOnly, fn (Builder $query): Builder => $query->publiclyVisible())
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('pet_name', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    /**
     * @return CursorPaginator<int, PetMemorial>
     */
    public function cursorPaginateForUser(User $user, int $perPage, ?string $search = null, ?string $cursor = null, bool $publicOnly = false): CursorPaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return PetMemorial::query()
            ->where('user_id', $user->id)
            ->when($publicOnly, fn (Builder $query): Builder => $query->publiclyVisible())
            ->with(['user', 'images'])
            ->withCount(['comments', 'likes'])
            ->when($search, function (Builder $query, string $search): void {
                $query->where(function (Builder $q) use ($search): void {
                    $q->where('pet_name', 'like', "%{$search}%")
                        ->orWhere('body', 'like', "%{$search}%");
                });
            })
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function find(int $id): PetMemorial
    {
        return PetMemorial::query()
            ->with(['user', 'images'])
            ->withCount(['comments', 'likes'])
            ->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): PetMemorial
    {
        return PetMemorial::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PetMemorial $memorial, array $data): PetMemorial
    {
        $memorial->update($data);

        return $memorial;
    }

    public function delete(PetMemorial $memorial): void
    {
        $memorial->delete();
    }

    public function incrementViews(PetMemorial $memorial): void
    {
        PetMemorial::withoutTimestamps(fn () => $memorial->increment('views_count'));
    }
}
