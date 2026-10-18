<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

#[Bind(CommentRepository::class)]
#[Scoped]
interface CommentRepositoryInterface
{
    /**
     * Paginate the top-level comments of a commentable model with their replies.
     *
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginateFor(Model $commentable, int $perPage): LengthAwarePaginator;

    /**
     * Cursor-paginate the top-level comments of a commentable model with their replies.
     *
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginateFor(Model $commentable, int $perPage, ?string $cursor = null): CursorPaginator;

    public function find(int $id): Comment;

    public function findParent(int $id): ?Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFor(Model $commentable, array $data): Comment;

    public function delete(Comment $comment): void;
}
