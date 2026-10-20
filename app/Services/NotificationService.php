<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Notification;
use App\Models\User;
use App\Repositories\NotificationRepositoryInterface;
use Illuminate\Contracts\Pagination\CursorPaginator;

class NotificationService implements NotificationServiceInterface
{
    public function __construct(
        protected NotificationRepositoryInterface $repository,
    ) {}

    public function paginate(User $user, int $perPage, ?string $cursor): CursorPaginator
    {
        return $this->repository->paginateFor($user, $perPage, $cursor);
    }

    public function unreadCount(User $user): int
    {
        return $this->repository->countUnread($user);
    }

    public function markAsRead(Notification $notification): void
    {
        $this->repository->markAsRead($notification);
    }

    public function markAllAsRead(User $user): int
    {
        return $this->repository->markAllAsRead($user);
    }
}
