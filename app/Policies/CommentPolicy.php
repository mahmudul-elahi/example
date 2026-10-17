<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Comment;
use App\Models\User;

class CommentPolicy
{
    public function viewAny(?User $user): bool
    {
        return true;
    }

    public function view(?User $user, Comment $comment): bool
    {
        return true;
    }

    /**
     * Any authenticated user may comment.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the author or an admin may edit a comment.
     */
    public function update(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }

    /**
     * Only the author or an admin may delete a comment.
     */
    public function delete(User $user, Comment $comment): bool
    {
        return $user->id === $comment->user_id || $user->isAdmin();
    }
}
