<?php

namespace App\Policies;

use App\Enums\FriendRequestStatus;
use App\Models\BlockedUser;
use App\Models\FriendRequest;
use App\Models\Story;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

class StoryPolicy
{
    /**
     * Determine whether the user can view any models.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Determine whether the user can view the model.
     */
    public function view(User $user, Story $story): bool
    {
        if ($story->expires_at->isPast()) {
            return false;
        }

        if ($story->user_id === $user->id) {
            return true;
        }

        $isBlocked = BlockedUser::query()
            ->where(function (Builder $query) use ($user, $story): void {
                $query->where('blocker_id', $user->id)->where('blocked_id', $story->user_id);
            })
            ->orWhere(function (Builder $query) use ($user, $story): void {
                $query->where('blocker_id', $story->user_id)->where('blocked_id', $user->id);
            })
            ->exists();

        if ($isBlocked) {
            return false;
        }

        return FriendRequest::query()
            ->where('status', FriendRequestStatus::Accepted)
            ->where(function (Builder $query) use ($user, $story): void {
                $query->where(function (Builder $query) use ($user, $story): void {
                    $query->where('sender_id', $user->id)->where('receiver_id', $story->user_id);
                })->orWhere(function (Builder $query) use ($user, $story): void {
                    $query->where('sender_id', $story->user_id)->where('receiver_id', $user->id);
                });
            })
            ->exists();
    }

    /**
     * Determine whether the user can create models.
     */
    public function create(User $user): bool
    {
        return $user->hasVerifiedEmail();
    }

    /**
     * Determine whether the user can delete the model.
     */
    public function delete(User $user, Story $story): bool
    {
        return $story->user_id === $user->id;
    }
}
