<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(BlockedUserRepository::class)]
#[Scoped]
interface BlockedUserRepositoryInterface
{
    /**
     * Determine if either user has blocked the other.
     */
    public function isBlockedEitherWay(int $userAId, int $userBId): bool;

    /**
     * Determine if $blockerId specifically blocked $blockedId.
     */
    public function hasBlocked(int $blockerId, int $blockedId): bool;

    public function block(User $blocker, User $blocked): BlockedUser;

    public function unblock(User $blocker, User $blocked): void;

    /**
     * @return CursorPaginator<int, BlockedUser>
     */
    public function paginateBlocked(User $user, int $perPage, ?string $cursor): CursorPaginator;
}
