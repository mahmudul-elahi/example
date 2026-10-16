<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogCategory;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class BlogCategoryRepository implements BlogCategoryRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, BlogCategory>
     */
    public function paginate(int $perPage, ?string $search): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return BlogCategory::query()
            ->withCount('posts')
            ->when($search, function (Builder $query, string $search): void {
                $query->where('name', 'like', "%{$search}%");
            })
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * @return Collection<int, BlogCategory>
     */
    public function all(): Collection
    {
        return BlogCategory::query()->orderBy('name')->get();
    }

    public function find(int $id): BlogCategory
    {
        return BlogCategory::query()->withCount('posts')->findOrFail($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogCategory
    {
        return BlogCategory::query()->create($data);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogCategory $category, array $data): BlogCategory
    {
        $category->update($data);

        return $category;
    }

    public function delete(BlogCategory $category): void
    {
        $category->delete();
    }
}
