<?php

declare(strict_types=1);

namespace App\Notifications\FriendRequest;

use App\Enums\NotificationType;
use App\Notifications\Base\InAppNotification;
use App\Notifications\Base\NotificationActor;

class FriendRequestAcceptedNotification extends InAppNotification
{
    public function __construct(
        public readonly int $friendRequestId,
        public readonly NotificationActor $actor,
    ) {}

    public function type(): NotificationType
    {
        return NotificationType::FriendRequestAccepted;
    }

    public function message(): string
    {
        return sprintf('%s accepted your friend request.', $this->actor->name);
    }

    /**
     * @return array<string, mixed>
     */
    public function payload(): array
    {
        return [
            'friend_request_id' => $this->friendRequestId,
            'actor' => $this->actor->toArray(),
        ];
    }
}
