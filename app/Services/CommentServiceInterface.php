<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(CommentService::class)]
#[Scoped]
interface CommentServiceInterface
{
    /**
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginate(BlogPost|PetMemorial|Product $commentable, int $perPage, ?string $cursor = null): CursorPaginator;

    public function find(int $id): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, BlogPost|PetMemorial|Product $commentable, array $data): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): void;
}
