<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Contracts\Database\Query\Builder;
use Illuminate\Contracts\Pagination\CursorPaginator;

class BlockedUserRepository implements BlockedUserRepositoryInterface
{
    public function isBlockedEitherWay(int $userAId, int $userBId): bool
    {
        return BlockedUser::query()
            ->where(function (Builder $query) use ($userAId, $userBId): void {
                $query->where('blocker_id', $userAId)
                    ->where('blocked_id', $userBId);
            })
            ->orWhere(function (Builder $query) use ($userAId, $userBId): void {
                $query->where('blocker_id', $userBId)
                    ->where('blocked_id', $userAId);
            })
            ->exists();
    }

    public function hasBlocked(int $blockerId, int $blockedId): bool
    {
        return BlockedUser::query()
            ->where('blocker_id', $blockerId)
            ->where('blocked_id', $blockedId)
            ->exists();
    }

    public function block(User $blocker, User $blocked): BlockedUser
    {
        return BlockedUser::query()->firstOrCreate([
            'blocker_id' => $blocker->id,
            'blocked_id' => $blocked->id,
        ]);
    }

    public function unblock(User $blocker, User $blocked): void
    {
        BlockedUser::query()
            ->where('blocker_id', $blocker->id)
            ->where('blocked_id', $blocked->id)
            ->delete();
    }

    /**
     * @return CursorPaginator<int, BlockedUser>
     */
    public function paginateBlocked(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return BlockedUser::query()
            ->where('blocker_id', $user->id)
            ->with(['blocked'])
            ->latest('id')
            ->cursorPaginate($perPage, ['*'], 'cursor', $cursor);
    }
}
