<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\FriendRequestStatus;
use App\Models\FriendRequest;
use App\Notifications\Base\NotificationActor;
use App\Notifications\FriendRequest\FriendRequestAcceptedNotification;
use App\Notifications\FriendRequest\FriendRequestReceivedNotification;
use App\Notifications\FriendRequest\FriendRequestRejectedNotification;

/**
 * Turns friend request lifecycle events into in-app notifications.
 *
 * Passing plain values to the queued notifications means the worker never has
 * to re-read the friend request, so it is safe to dispatch inside the service's
 * transaction.
 */
class FriendRequestObserver
{
    /**
     * Notify the receiver when a new pending request is created.
     */
    public function created(FriendRequest $friendRequest): void
    {
        if ($friendRequest->status !== FriendRequestStatus::Pending) {
            return;
        }

        $friendRequest->loadMissing(['sender', 'receiver']);

        $this->notifyReceiver($friendRequest);
    }

    /**
     * Notify the receiver when a rejected request is sent again, or notify the
     * sender when the receiver accepts or rejects it.
     */
    public function updated(FriendRequest $friendRequest): void
    {
        if (! $friendRequest->wasChanged('status')) {
            return;
        }

        $friendRequest->loadMissing(['sender', 'receiver']);

        if ($friendRequest->status === FriendRequestStatus::Pending) {
            $this->notifyReceiver($friendRequest);

            return;
        }

        if ($friendRequest->status === FriendRequestStatus::Accepted) {
            $this->notifySender($friendRequest, accepted: true);

            return;
        }

        if ($friendRequest->status === FriendRequestStatus::Rejected) {
            $this->notifySender($friendRequest, accepted: false);
        }
    }

    private function notifyReceiver(FriendRequest $friendRequest): void
    {
        $sender = $friendRequest->sender;

        $friendRequest->receiver->notify(new FriendRequestReceivedNotification(
            friendRequestId: $friendRequest->id,
            actor: NotificationActor::fromUser($sender),
        ));
    }

    private function notifySender(FriendRequest $friendRequest, bool $accepted): void
    {
        $receiver = $friendRequest->receiver;
        $actor = NotificationActor::fromUser($receiver);

        $notification = $accepted
            ? new FriendRequestAcceptedNotification(
                friendRequestId: $friendRequest->id,
                actor: $actor,
            )
            : new FriendRequestRejectedNotification(
                friendRequestId: $friendRequest->id,
                actor: $actor,
            );

        $friendRequest->sender->notify($notification);
    }
}
