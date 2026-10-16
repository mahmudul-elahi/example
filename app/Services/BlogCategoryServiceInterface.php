<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogCategory;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

#[Bind(BlogCategoryService::class)]
#[Scoped]
interface BlogCategoryServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, BlogCategory>
     */
    public function paginate(int $perPage, ?string $search): LengthAwarePaginator;

    /**
     * @return Collection<int, BlogCategory>
     */
    public function all(): Collection;

    public function find(int $id): BlogCategory;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogCategory;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogCategory $category, array $data): BlogCategory;

    public function delete(BlogCategory $category): void;
}
