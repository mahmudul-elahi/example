<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\BlockedUser;
use App\Models\User;
use App\Repositories\BlockedUserRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;
use Illuminate\Validation\ValidationException;

class BlockedUserService implements BlockedUserServiceInterface
{
    public function __construct(
        protected BlockedUserRepositoryInterface $blockedUsers,
    ) {}

    public function block(User $currentUser, User $target): BlockedUser
    {
        if ($currentUser->is($target)) {
            throw ValidationException::withMessages([
                'user_id' => 'You cannot block yourself.',
            ]);
        }

        return $this->blockedUsers->block($currentUser, $target);
    }

    public function unblock(User $currentUser, User $target): void
    {
        $this->blockedUsers->unblock($currentUser, $target);
    }

    /**
     * @return CursorPaginator<int, BlockedUser>
     */
    public function listBlocked(User $currentUser, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->blockedUsers->paginateBlocked($currentUser, $perPage, $cursor);
    }

    public function isBlockedEitherWay(int $userAId, int $userBId): bool
    {
        return $this->blockedUsers->isBlockedEitherWay($userAId, $userBId);
    }
}
