<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Comment;
use App\Models\User;
use App\Repositories\CommentRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CommentService implements CommentServiceInterface
{
    public function __construct(
        protected CommentRepositoryInterface $repository,
    ) {}

    /**
     * @return LengthAwarePaginator<int, Comment>
     */
    public function paginate(Model $commentable, int $perPage): LengthAwarePaginator
    {
        return $this->repository->paginateFor($commentable, $perPage);
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
    }
}
