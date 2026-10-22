<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlogPost;
use App\Models\Comment;
use App\Models\PetMemorial;
use App\Models\Product;
use Illuminate\Contracts\Pagination\CursorPaginator;

class CommentRepository implements CommentRepositoryInterface
{
    /**
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginateFor(BlogPost|PetMemorial|Product $commentable, int $perPage, ?string $cursor = null): CursorPaginator
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
    public function createFor(BlogPost|PetMemorial|Product $commentable, array $data): Comment
    {
        return $commentable->comments()->create($data);
    }

    public function delete(Comment $comment): void
    {
        // Replies hang off the top-level comment, so remove them together with
        // it. Otherwise deleting a thread leaves orphaned replies behind.
        $comment->replies()->delete();

        $comment->delete();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): Comment
    {
        $comment->update($data);

        return $comment;
    }
}
