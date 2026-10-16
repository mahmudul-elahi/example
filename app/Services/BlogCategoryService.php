<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogCategory;
use App\Repositories\BlogCategoryRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BlogCategoryService implements BlogCategoryServiceInterface
{
    public function __construct(
        protected BlogCategoryRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, BlogCategory>
     */
    public function paginate(int $perPage, ?string $search): LengthAwarePaginator
    {
        return $this->repository->paginate($perPage, $search);
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    public function all(): Collection
    {
        return $this->repository->all();
    }

    public function find(int $id): BlogCategory
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogCategory
    {
        return $this->repository->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogCategory $category, array $data): BlogCategory
    {
        return $this->repository->update($category, $data);
    }

    public function delete(BlogCategory $category): void
    {
        $this->repository->delete($category);
    }
}
