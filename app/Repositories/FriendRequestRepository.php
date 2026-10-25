<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Models\User;
use Closure;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Query\JoinClause;
use Illuminate\Support\Facades\DB;

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
     * Verified users the given user has no friendship or pending request with,
     * ranked by the number of friends both users share.
     *
     * @return LengthAwarePaginator<int, User>
     */
    public function paginateSuggestions(User $user, int $perPage): LengthAwarePaginator
    {
        return User::query()
            ->select('users.*')
            ->selectSub($this->mutualFriendsCountQuery($user), 'mutual_friends_count')
            ->whereKeyNot($user->id)
            ->whereNotNull('users.email_verified_at')
            ->whereNotExists($this->existingConnectionQuery($user))
            ->orderByDesc('mutual_friends_count')
            ->orderBy('users.name')
            ->orderBy('users.id')
            ->paginate($this->clampPerPage($perPage));
    }

    /**
     * A directed friendship edge per accepted request, so both directions of a
     * friendship can be joined between the user and a candidate.
     */
    private function friendshipEdgesQuery(): Builder
    {
        $sent = DB::table('friend_requests')
            ->select('sender_id as user_id', 'receiver_id as friend_id')
            ->where('status', FriendRequestStatus::Accepted->value)
            ->whereNull('deleted_at');

        $received = DB::table('friend_requests')
            ->select('receiver_id as user_id', 'sender_id as friend_id')
            ->where('status', FriendRequestStatus::Accepted->value)
            ->whereNull('deleted_at');

        return $sent->unionAll($received);
    }

    /**
     * Count the candidate's friends that are also friends of the given user.
     */
    private function mutualFriendsCountQuery(User $user): Builder
    {
        return DB::query()
            ->fromSub($this->friendshipEdgesQuery(), 'my_friends')
            ->joinSub(
                $this->friendshipEdgesQuery(),
                'their_friends',
                function (JoinClause $join): void {
                    $join->on('their_friends.user_id', '=', 'my_friends.friend_id');
                }
            )
            ->where('my_friends.user_id', $user->id)
            ->whereColumn('their_friends.friend_id', 'users.id')
            ->selectRaw('COUNT(DISTINCT my_friends.friend_id)');
    }

    /**
     * Exclude users already connected by an accepted friendship or a pending
     * request in either direction. Rejected and cancelled requests are not
     * connections, so those users stay suggestible.
     */
    private function existingConnectionQuery(User $user): Closure
    {
        return function (Builder $query) use ($user): void {
            $query->from('friend_requests')
                ->whereNull('deleted_at')
                ->whereIn('status', [
                    FriendRequestStatus::Pending->value,
                    FriendRequestStatus::Accepted->value,
                ])
                ->where(function (Builder $query) use ($user): void {
                    $query->where(function (Builder $query) use ($user): void {
                        $query->where('sender_id', $user->id)
                            ->whereColumn('receiver_id', 'users.id');
                    })->orWhere(function (Builder $query) use ($user): void {
                        $query->where('receiver_id', $user->id)
                            ->whereColumn('sender_id', 'users.id');
                    });
                });
        };
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
