<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogPost;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

#[Bind(BlogPostService::class)]
#[Scoped]
interface BlogPostServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, BlogPost>
     */
    public function paginate(int $perPage, ?string $search, bool $publishedOnly): LengthAwarePaginator;

    public function find(int $id): BlogPost;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, array $data): BlogPost;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(BlogPost $post, array $data): BlogPost;

    public function delete(BlogPost $post): void;

    public function recordView(BlogPost $post): void;
}
