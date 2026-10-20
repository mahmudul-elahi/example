<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * The stable discriminator stored on every in-app notification.
 *
 * Clients switch on these values, so they must never be renamed. Add a case
 * whenever a new notification type is introduced; the payload for the type
 * lives in its notification class under `app/Notifications`.
 */
enum NotificationType: string
{
    case FriendRequestReceived = 'friend_request_received';
    case FriendRequestAccepted = 'friend_request_accepted';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
