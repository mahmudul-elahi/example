<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Container\Attributes\Bind;
use Illuminate\Container\Attributes\Scoped;
use Illuminate\Contracts\Pagination\CursorPaginator;

#[Bind(NotificationRepository::class)]
#[Scoped]
interface NotificationRepositoryInterface
{
    /**
     * The notifications addressed to a user, newest first.
     *
     * @return CursorPaginator<int, Notification>
     */
    public function paginateFor(User $user, int $perPage, ?string $cursor): CursorPaginator;

    /**
     * How many of the user's notifications are still unread.
     */
    public function countUnread(User $user): int;

    public function markAsRead(Notification $notification): void;

    /**
     * Mark every unread notification of the user as read.
     *
     * @return int The number of notifications marked as read.
     */
    public function markAllAsRead(User $user): int;
}
