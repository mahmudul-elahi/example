<?php

namespace App\Repositories;

use App\Enums\FriendRequestStatus;
use App\Models\Story;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder as QueryBuilder;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Support\Carbon;

class StoryRepository implements StoryRepositoryInterface
{
    /** @param array{user_id: int, media_path: string, media_type: string, caption: string|null, expires_at: Carbon} $data */
    public function create(array $data): Story
    {
        return Story::query()->create($data)->load('user');
    }

    /** @return CursorPaginator<int, Story> */
    public function paginateActiveForFriends(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return Story::query()
            ->where('expires_at', '>', now())
            ->whereExists(function (QueryBuilder $query) use ($user): void {
                $query->selectRaw('1')
                    ->from('friend_requests')
                    ->whereNull('friend_requests.deleted_at')
                    ->where('friend_requests.status', FriendRequestStatus::Accepted->value)
                    ->where(function (QueryBuilder $query) use ($user): void {
                        $query->where(function (QueryBuilder $query) use ($user): void {
                            $query->where('friend_requests.sender_id', $user->id)
                                ->whereColumn('friend_requests.receiver_id', 'stories.user_id');
                        })->orWhere(function (QueryBuilder $query) use ($user): void {
                            $query->where('friend_requests.receiver_id', $user->id)
                                ->whereColumn('friend_requests.sender_id', 'stories.user_id');
                        });
                    });
            })
            ->whereNotExists(function (QueryBuilder $query) use ($user): void {
                $query->selectRaw('1')
                    ->from('blocked_users')
                    ->where(function (QueryBuilder $query) use ($user): void {
                        $query->where(function (QueryBuilder $query) use ($user): void {
                            $query->where('blocked_users.blocker_id', $user->id)
                                ->whereColumn('blocked_users.blocked_id', 'stories.user_id');
                        })->orWhere(function (QueryBuilder $query) use ($user): void {
                            $query->whereColumn('blocked_users.blocker_id', 'stories.user_id')
                                ->where('blocked_users.blocked_id', $user->id);
                        });
                    });
            })
            ->with('user')
            ->orderByDesc('id')
            ->cursorPaginate($this->clampPerPage($perPage), ['*'], 'cursor', $cursor);
    }

    /** @return CursorPaginator<int, Story> */
    public function paginateActiveForUser(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return Story::query()
            ->where('user_id', $user->id)
            ->where('expires_at', '>', now())
            ->with('user')
            ->orderByDesc('id')
            ->cursorPaginate($this->clampPerPage($perPage), ['*'], 'cursor', $cursor);
    }

    public function delete(Story $story): void
    {
        $story->delete();
    }

    private function clampPerPage(int $perPage): int
    {
        return min(max($perPage, 1), 100);
    }
}
