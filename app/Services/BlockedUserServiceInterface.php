<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(BlockedUserService::class)]
#[Scoped]
interface BlockedUserServiceInterface
{
    public function block(User $currentUser, User $target): BlockedUser;

    public function unblock(User $currentUser, User $target): void;

    /**
     * @return CursorPaginator<int, BlockedUser>
     */
    public function listBlocked(User $currentUser, int $perPage, ?string $cursor): CursorPaginator;

    public function isBlockedEitherWay(int $userAId, int $userBId): bool;
}
