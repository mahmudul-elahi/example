<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Model;

#[Bind(CommentService::class)]
#[Scoped]
interface CommentServiceInterface
{
    /**
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginate(Model $commentable, int $perPage, ?string $cursor = null): CursorPaginator;

    public function find(int $id): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, Model $commentable, array $data): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): Comment;

    public function delete(Comment $comment): void;
}
