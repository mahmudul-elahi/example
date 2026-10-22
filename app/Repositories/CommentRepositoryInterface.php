<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(CommentRepository::class)]
#[Scoped]
interface CommentRepositoryInterface
{
    /**
     * Cursor-paginate the top-level comments of a commentable model with their replies.
     *
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginateFor(BlogPost|PetMemorial|Product $commentable, int $perPage, ?string $cursor = null): CursorPaginator;

    public function find(int $id): Comment;

    public function findParent(int $id): ?Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFor(BlogPost|PetMemorial|Product $commentable, array $data): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): void;
}
