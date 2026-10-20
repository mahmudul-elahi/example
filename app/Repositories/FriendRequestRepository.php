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
        $friendIds = FriendRequest::query()
            ->where('status', FriendRequestStatus::Accepted)
            ->where(function (Builder $query) use ($user): void {
                $query->where('sender_id', $user->id)->orWhere('receiver_id', $user->id);
            })
            ->get()
            ->map(fn (FriendRequest $friendRequest): int => $friendRequest->sender_id === $user->id
                ? $friendRequest->receiver_id
                : $friendRequest->sender_id)
            ->all();

        return User::query()
            ->whereIn('id', $friendIds)
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
