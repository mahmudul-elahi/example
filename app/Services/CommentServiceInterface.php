<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

#[Bind(CommentService::class)]
#[Scoped]
interface CommentServiceInterface
{
    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(Model $commentable, int $perPage): LengthAwarePaginator;

    public function find(int $id): Comment;

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, Model $commentable, array $data): Comment;

    public function delete(Comment $comment): void;
}
