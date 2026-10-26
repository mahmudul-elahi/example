<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PetMemorial;
use App\Models\User;
use App\Repositories\PetMemorialRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class PetMemorialService implements PetMemorialServiceInterface
{
    public function __construct(
        protected PetMemorialRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, PetMemorial>
     */
    public function paginate(int $perPage, ?string $search, bool $publicOnly): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $search, $publicOnly);
    }

    /**
     * @return CursorPaginator<int, PetMemorial>
     */
    public function cursorPaginate(int $perPage, ?string $search, bool $publicOnly, ?string $cursor = null): CursorPaginator
    {
        return $this->repository->cursorPaginate($perPage, $search, $publicOnly, $cursor);
    }

    /**
     * @return CursorPaginator<int, PetMemorial>
     */
    public function mine(User $user, int $perPage, ?string $search = null, ?string $cursor = null): CursorPaginator
    {
        return $this->repository->cursorPaginateForUser($user, $perPage, $search, $cursor, false);
    }

    /**
     * @return CursorPaginator<int, PetMemorial>
     */
    public function forUser(User $user, int $perPage, ?string $search = null, ?string $cursor = null, bool $publicOnly = true): CursorPaginator
    {
        return $this->repository->cursorPaginateForUser($user, $perPage, $search, $cursor, $publicOnly);
    }

    public function find(int $id): PetMemorial
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): PetMemorial
    {
        $data['user_id'] = $author->id;
        $data['cover_image'] = $this->storeCoverImage($data['cover_image'] ?? null);

        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PetMemorial $memorial, array $data): PetMemorial
    {
        $obsoleteCover = array_key_exists('cover_image', $data) ? $memorial->cover_image : null;

        if (array_key_exists('cover_image', $data)) {
            $data['cover_image'] = $this->storeCoverImage($data['cover_image']);
        }

        $memorial = $this->repository->update($memorial, $data);

        if ($obsoleteCover !== null) {
            Storage::delete($obsoleteCover);
        }

        return $memorial;
    }

    public function delete(PetMemorial $memorial): void
    {
        $this->repository->delete($memorial);
    }

    public function recordView(PetMemorial $memorial): void
    {
        $this->repository->incrementViews($memorial);
    }

    /**
     * Store an uploaded cover image on the default disk, or clear the value.
     */
    private function storeCoverImage(mixed $cover): ?string
    {
        if ($cover instanceof UploadedFile) {
            return $cover->store('memorials');
        }

        return is_string($cover) ? $cover : null;
    }
}
