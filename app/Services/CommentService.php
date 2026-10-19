<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Repositories\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CommentService implements CommentServiceInterface
{
    public function __construct(
        protected CommentRepositoryInterface $repository,
    ) {}

    /**
     * @return CursorPaginator<int, Comment>
     */
    public function cursorPaginate(Model $commentable, int $perPage, ?string $cursor = null): CursorPaginator
    {
        return $this->repository->cursorPaginateFor($commentable, $perPage, $cursor);
    }

    public function find(int $id): Comment
    {
        return $this->repository->find($id);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(User $author, Model $commentable, array $data): Comment
    {
        $parentId = $data['parent_id'] ?? null;

        if ($parentId !== null) {
            $this->assertParentBelongsTo($commentable, (int) $parentId);
        }

        $data['user_id'] = $author->id;

        return $this->repository->createFor($commentable, $data);
    }

    public function delete(Comment $comment): void
    {
        $this->repository->delete($comment);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Comment $comment, array $data): Comment
    {
        return $this->repository->update($comment, $data);
    }

    /**
     * @throws ValidationException
     */
    private function assertParentBelongsTo(Model $commentable, int $parentId): void
    {
        $parent = $this->repository->findParent($parentId);

        if (
            ! $parent instanceof Comment
            || $parent->commentable_type !== $commentable->getMorphClass()
            || $parent->commentable_id !== $commentable->getKey()
        ) {
            throw ValidationException::withMessages([
                'parent_id' => 'The parent comment does not belong to this thread.',
            ]);
        }

        if ($parent->parent_id !== null) {
            throw ValidationException::withMessages([
                'parent_id' => 'You can only reply to a top-level comment.',
            ]);
        }
    }
}
