<?php

declare(strict_types=1);

namespace App\Policies;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;

class FriendRequestPolicy
{
    /**
     * Any authenticated user may browse their own friend requests.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * Any authenticated user may send a friend request.
     */
    public function create(User $user): bool
    {
        return true;
    }

    /**
     * Only the receiver of a pending request may accept or reject it.
     */
    public function respond(User $user, FriendRequest $friendRequest): bool
    {
        return $friendRequest->status === FriendRequestStatus::Pending
            && $user->id === $friendRequest->receiver_id;
    }

    /**
     * Only the sender of a pending request may cancel it.
     */
    public function cancel(User $user, FriendRequest $friendRequest): bool
    {
        return $friendRequest->status === FriendRequestStatus::Pending
            && $user->id === $friendRequest->sender_id;
    }
}
