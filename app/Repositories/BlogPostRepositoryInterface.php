<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogPost;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(BlogPostRepository::class)]
#[Scoped]
interface BlogPostRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly): LengthAwarePaginator;

    public function find(int $id): BlogPost;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): BlogPost;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost;

    public function delete(BlogPost $post): void;

    public function incrementViews(BlogPost $post): void;
}
