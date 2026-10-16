<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogCategory;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

#[Bind(BlogCategoryRepository::class)]
#[Scoped]
interface BlogCategoryRepositoryInterface
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
