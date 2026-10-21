<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(FriendRequestRepository::class)]
#[Scoped]
interface FriendRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FriendRequest;

    public function find(int $id): FriendRequest;

    /**
     * Lock and reload a request for a state transition inside a transaction.
     */
    public function findForUpdate(int $id): FriendRequest;

    /**
     * The request between two users, in either direction, if one exists.
     *
     * Cancelled and removed friendships are soft-deleted, so this includes
     * trashed rows to keep the notification history for the pair.
     */
    public function findBetween(int $senderId, int $receiverId): ?FriendRequest;

    /**
     * The pending requests a user has received.
     *
     * @return CursorPaginator<int, FriendRequest>
     */
    public function paginateReceived(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /**
     * How many pending requests the user has received.
     */
    public function countPendingReceived(User $user): int;

    /**
     * The users the given user is friends with (an accepted request either way).
     *
     * @return CursorPaginator<int, User>
     */
    public function paginateFriends(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FriendRequest $friendRequest, array $data): FriendRequest;

    public function delete(FriendRequest $friendRequest): void;
}
