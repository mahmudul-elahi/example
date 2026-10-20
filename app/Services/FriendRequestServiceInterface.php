<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(FriendRequestService::class)]
#[Scoped]
interface FriendRequestServiceInterface
{
    /**
     * Send a friend request from one user to another.
     */
    public function send(User $sender, User $receiver): FriendRequest;

    /**
     * Accept a pending request the user received.
     */
    public function accept(User $user, FriendRequest $friendRequest): FriendRequest;

    /**
     * Reject a pending request the user received.
     */
    public function reject(User $user, FriendRequest $friendRequest): FriendRequest;

    /**
     * Cancel a pending request the user sent.
     */
    public function cancel(User $user, FriendRequest $friendRequest): void;

    /**
     * Remove an accepted friendship so either user can send a new request.
     */
    public function unfriend(User $user, User $friend): void;

    /**
     * The pending requests the user has received.
     *
     * @return CursorPaginator<int, FriendRequest>
     */
    public function received(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /**
     * How many pending requests the user has received.
     */
    public function pendingCount(User $user): int;

    /**
     * The users the given user is friends with.
     *
     * @return CursorPaginator<int, User>
     */
    public function friends(User $user, int $perPage, ?string $cursor): CursorPaginator;
}
