<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\PostStatus;
use App\Models\BlogPost;
use App\Models\User;

class BlogPostPolicy
{
    /**
     * Anyone may browse the blog listing.
     */
    public function viewAny(?User $user): bool
    {
        return true;
    }

    /**
     * A post is visible once it is live (published and due), or to its author
     * and admins. Mirrors the `published` query scope.
     */
    public function view(?User $user, BlogPost $post): bool
    {
        if ($user?->isAdmin() === true || ($user instanceof User && $user->id === $post->user_id)) {
            return true;
        }

        return $post->status === PostStatus::Published
            && ($post->published_at === null || ! $post->published_at->isFuture());
    }

    /**
     * Only admins publish blog content.
     */
    public function create(User $user): bool
    {
        return $user->isAdmin();
    }

    public function update(User $user, BlogPost $post): bool
    {
        return $user->isAdmin();
    }

    public function delete(User $user, BlogPost $post): bool
    {
        return $user->isAdmin();
    }
}
