<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;

class FriendRequestRepository implements FriendRequestRepositoryInterface
{
    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data): FriendRequest
    {
        return FriendRequest::query()->create($data);
    }

    public function find(int $id): FriendRequest
    {
        return FriendRequest::query()
            ->with(['sender', 'receiver'])
            ->findOrFail($id);
    }

    public function findForUpdate(int $id): FriendRequest
    {
        return FriendRequest::query()
            ->lockForUpdate()
            ->findOrFail($id);
    }

    public function findBetween(int $senderId, int $receiverId): ?FriendRequest
    {
        return FriendRequest::query()
            ->withTrashed()
            ->where(function (Builder $query) use ($senderId, $receiverId): void {
                $query->where('sender_id', $senderId)->where('receiver_id', $receiverId);
            })
            ->orWhere(function (Builder $query) use ($senderId, $receiverId): void {
                $query->where('sender_id', $receiverId)->where('receiver_id', $senderId);
            })
            ->first();
    }

    public function paginateReceived(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return FriendRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', FriendRequestStatus::Pending)
            ->with(['sender', 'receiver'])
            ->latest('id')
            ->cursorPaginate($this->clampPerPage($perPage), ['*'], 'cursor', $cursor);
    }

    public function countPendingReceived(User $user): int
    {
        return FriendRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', FriendRequestStatus::Pending)
            ->count();
    }

    public function paginateFriends(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        $sent = FriendRequest::query()
            ->where('sender_id', $user->id)
            ->where('status', FriendRequestStatus::Accepted)
            ->select('receiver_id as friend_id');

        $received = FriendRequest::query()
            ->where('receiver_id', $user->id)
            ->where('status', FriendRequestStatus::Accepted)
            ->select('sender_id as friend_id');

        return User::query()
            ->whereIn('id', $sent->union($received))
            ->orderBy('name')
            ->orderBy('id')
            ->cursorPaginate($this->clampPerPage($perPage), ['*'], 'cursor', $cursor);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(FriendRequest $friendRequest, array $data): FriendRequest
    {
        $friendRequest->update($data);

        return $friendRequest;
    }

    public function delete(FriendRequest $friendRequest): void
    {
        $friendRequest->delete();
    }

    private function clampPerPage(int $perPage): int
    {
        return min(max($perPage, 1), 100);
    }
}
