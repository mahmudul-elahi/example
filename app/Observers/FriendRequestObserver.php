<?php

declare(strict_types=1);

namespace App\Observers;

use App\Enums\FriendRequestStatus;
use App\Enums\NotificationType;
use App\Models\FriendRequest;
use App\Notifications\Base\NotificationActor;
use App\Notifications\FriendRequest\FriendRequestAcceptedNotification;
use App\Notifications\FriendRequest\FriendRequestReceivedNotification;

/**
 * Turns the first friend request and the first acceptance between two users
 * into in-app notifications. Later request/accept cycles stay silent because
 * the pair's row is soft-deleted rather than removed, so its notification
 * flags survive cancellations and unfriending.
 *
 * The "request received" notification is an action item for a pending request,
 * so it is removed as soon as the request is resolved or cancelled. The
 * sender's accepted notification remains as history.
 *
 * Passing plain values to the queued notifications means the worker never has
 * to re-read the friend request, so it is safe to dispatch inside the service's
 * transaction.
 */
class FriendRequestObserver
{
    /**
     * Notify the receiver the first time a pending request is created.
     */
    public function created(FriendRequest $friendRequest): void
    {
        if ($friendRequest->status !== FriendRequestStatus::Pending) {
            return;
        }

        $friendRequest->loadMissing(['sender', 'receiver']);

        $this->notifyReceiverOnce($friendRequest);
    }

    /**
     * Drop the receiver's action item when the request is resolved, and notify
     * the sender the first time it is accepted.
     */
    public function updated(FriendRequest $friendRequest): void
    {
        if (! $friendRequest->wasChanged('status')) {
            return;
        }

        $friendRequest->loadMissing(['sender', 'receiver']);

        if ($friendRequest->status === FriendRequestStatus::Pending) {
            $this->notifyReceiverOnce($friendRequest);

            return;
        }

        $this->forgetReceivedNotification($friendRequest);

        if ($friendRequest->status === FriendRequestStatus::Accepted) {
            $this->notifySenderOnce($friendRequest);
        }
    }

    /**
     * Drop the receiver's action item when the request is cancelled or the
     * friendship is removed.
     */
    public function deleted(FriendRequest $friendRequest): void
    {
        $this->forgetReceivedNotification($friendRequest);
    }

    private function notifyReceiverOnce(FriendRequest $friendRequest): void
    {
        if ($friendRequest->request_notified_at !== null) {
            return;
        }

        // Keep exactly one action item if a legacy row still has one.
        $this->forgetReceivedNotification($friendRequest);

        $friendRequest->receiver->notify(new FriendRequestReceivedNotification(
            friendRequestId: $friendRequest->id,
            actor: NotificationActor::fromUser($friendRequest->sender),
        ));

        $friendRequest->updateQuietly(['request_notified_at' => now()]);
    }

    private function notifySenderOnce(FriendRequest $friendRequest): void
    {
        if ($friendRequest->accepted_notified_at !== null) {
            return;
        }

        $friendRequest->sender->notify(new FriendRequestAcceptedNotification(
            friendRequestId: $friendRequest->id,
            actor: NotificationActor::fromUser($friendRequest->receiver),
        ));

        $friendRequest->updateQuietly(['accepted_notified_at' => now()]);
    }

    /**
     * Remove the receiver's pending "friend request received" notification.
     */
    private function forgetReceivedNotification(FriendRequest $friendRequest): void
    {
        $receiver = $friendRequest->receiver;

        if ($receiver === null) {
            return;
        }

        $receiver->notifications()
            ->where('type', NotificationType::FriendRequestReceived->value)
            ->where('data->data->friend_request_id', $friendRequest->id)
            ->delete();
    }
}
