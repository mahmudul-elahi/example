<?php

declare(strict_types=1);

namespace App\Policies;

use App\Models\Notification;
use App\Models\User;

class NotificationPolicy
{
    /**
     * Any authenticated user may browse their own notifications.
     */
    public function viewAny(User $user): bool
    {
        return true;
    }

    /**
     * A user may only act on notifications addressed to them.
     */
    public function markAsRead(User $user, Notification $notification): bool
    {
        return $notification->notifiable_type === $user->getMorphClass()
            && (int) $notification->notifiable_id === $user->id;
    }
}
