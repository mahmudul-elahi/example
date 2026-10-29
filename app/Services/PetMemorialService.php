<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\PetMemorial;
use App\Models\User;
use App\Repositories\PetMemorialRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;

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
        /** @var array<int, mixed> $images */
        $images = $data['images'] ?? [];
        unset($data['images']);

        $memorial = $this->repository->create($data);

        if (! empty($images)) {
            $this->storeImages($memorial, $images);
        }

        return $memorial;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(PetMemorial $memorial, array $data): PetMemorial
    {
        /** @var array<int, mixed>|null $images */
        $images = $data['images'] ?? null;
        unset($data['images']);

        $memorial = $this->repository->update($memorial, $data);

        if (is_array($images) && ! empty($images)) {
            $this->storeImages($memorial, $images);
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
     * Store uploaded memorial images on the default disk.
     *
     * @param  array<int, mixed>  $images
     */
    private function storeImages(PetMemorial $memorial, array $images): void
    {
        foreach ($images as $image) {
            if ($image instanceof UploadedFile) {
                $path = $image->store('memorials');
                $memorial->images()->create(['path' => $path]);
            }
        }
    }
}
