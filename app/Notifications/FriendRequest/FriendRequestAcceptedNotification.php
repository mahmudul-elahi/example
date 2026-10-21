<?php

declare(strict_types=1);

namespace App\Notifications\FriendRequest;

use App\Enums\FriendRequestStatus;
use App\Enums\NotificationType;
use App\Models\FriendRequest;
use App\Models\User;
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
     * Skip the notification if the friendship ended before the queued job ran.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        if (! $notifiable instanceof User) {
            return false;
        }

        $friendRequest = FriendRequest::withTrashed()->find($this->friendRequestId);

        return $friendRequest instanceof FriendRequest
            && ! $friendRequest->trashed()
            && $friendRequest->status === FriendRequestStatus::Accepted
            && $friendRequest->sender_id === $notifiable->id;
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
