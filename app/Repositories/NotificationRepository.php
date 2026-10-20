<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\Notification;
use App\Models\User;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    public function paginateFor(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $user->notifications()
            ->orderByDesc('id')
            ->cursorPaginate($this->clampPerPage($perPage), ['*'], 'cursor', $cursor);
    }

    public function countUnread(User $user): int
    {
        return $user->unreadNotifications()->count();
    }

    public function markAsRead(Notification $notification): void
    {
        $notification->markAsRead();
    }

    public function markAllAsRead(User $user): int
    {
        return $user->unreadNotifications()->update(['read_at' => now()]);
    }

    private function clampPerPage(int $perPage): int
    {
        return min(max($perPage, 1), 100);
    }
}
