<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Comment;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginateFor(Model $commentable, int $perPage): LengthAwarePaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return $commentable->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->withCount('replies')
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginateFor(Model $commentable, int $perPage, ?string $cursor = null): CursorPaginator
    {
        $perPage = min(max($perPage, 1), 100);

        return $commentable->comments()
            ->whereNull('parent_id')
            ->with(['user', 'replies.user'])
            ->withCount('replies')
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }

    public function find(int $id): Comment
    {
        return Comment::query()
            ->with(['user', 'replies.user'])
            ->withCount('replies')
            ->findOrFail($id);
    }

    public function findParent(int $id): ?Comment
    {
        return Comment::query()->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function createFor(Model $commentable, array $data): Comment
    {
        return $commentable->comments()->create($data);
    }

    public function delete(Comment $comment): void
    {
        $comment->delete();
    }
}
