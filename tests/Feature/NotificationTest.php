<?php

declare(strict_types=1);

use App\Enums\FriendRequestStatus;
use App\Enums\NotificationType;
use App\Enums\Roles;
use App\Models\FriendRequest;
use App\Models\User;
use App\Notifications\Base\NotificationActor;
use App\Notifications\FriendRequest\FriendRequestAcceptedNotification;
use App\Notifications\FriendRequest\FriendRequestReceivedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function notifyPendingFriendRequest(User $recipient, User $sender): void
{
    $friendRequest = FriendRequest::factory()->from($sender)->to($recipient)->createQuietly();

    $recipient->notify(new FriendRequestReceivedNotification(
        $friendRequest->id,
        NotificationActor::fromUser($sender),
    ));
}

it('notifies the receiver when a friend request is sent', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    expect($receiver->notifications()->count())->toBe(1);

    $friendRequest = FriendRequest::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    expect($friendRequest->request_notified_at)->not->toBeNull();

    $notification = $receiver->notifications()->first();

    expect($notification->type)->toBe(NotificationType::FriendRequestReceived->value)
        ->and($notification->data['message'])->toBe("{$sender->name} sent you a friend request.")
        ->and($notification->data['data']['actor']['id'])->toBe($sender->id)
        ->and($notification->data['data']['friend_request_id'])->toBeInt()
        ->and($notification->read_at)->toBeNull();
});

it('notifies the sender when the receiver accepts the request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/accept")
        ->assertOk();

    $friendRequest->refresh();
    $notification = $sender->notifications()->first();

    expect($friendRequest->accepted_notified_at)->not->toBeNull()
        ->and($notification)->not->toBeNull()
        ->and($notification->type)->toBe(NotificationType::FriendRequestAccepted->value)
        ->and($notification->data['message'])->toBe("{$receiver->name} accepted your friend request.")
        ->and($notification->data['data']['actor']['id'])->toBe($receiver->id)
        ->and($receiver->notifications()->count())->toBe(0);
});

it('stays silent when the receiver rejects the request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    expect($receiver->notifications()->count())->toBe(1);

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/reject")
        ->assertOk();

    expect($sender->notifications()->count())->toBe(0)
        ->and($receiver->notifications()->count())->toBe(0);
});

it('stays silent when a rejected request is resent', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    $friendRequest = FriendRequest::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    expect($receiver->notifications()->count())->toBe(1);

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/reject")
        ->assertOk();

    expect($receiver->notifications()->count())->toBe(0);

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    expect($receiver->notifications()->count())->toBe(0)
        ->and($sender->notifications()->count())->toBe(0);
});

it('removes the received notification and stays silent when a request is cancelled', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    $friendRequest = FriendRequest::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    expect($receiver->notifications()->count())->toBe(1);

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/friend-requests/{$friendRequest->id}")
        ->assertOk();

    $this->assertSoftDeleted('friend_requests', ['id' => $friendRequest->id]);

    expect($receiver->notifications()->count())->toBe(0)
        ->and($sender->notifications()->count())->toBe(0);
});

it('stays silent when a cancelled request is resent', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    $friendRequest = FriendRequest::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/friend-requests/{$friendRequest->id}")
        ->assertOk();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    $this->assertDatabaseHas('friend_requests', [
        'id' => $friendRequest->id,
        'status' => FriendRequestStatus::Pending->value,
        'deleted_at' => null,
    ]);

    expect($receiver->notifications()->count())->toBe(0)
        ->and($sender->notifications()->count())->toBe(0);
});

it('notifies only on the first request and first acceptance', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    $friendRequest = FriendRequest::query()
        ->where('sender_id', $sender->id)
        ->where('receiver_id', $receiver->id)
        ->firstOrFail();

    expect($receiver->notifications()->count())->toBe(1);

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/accept")
        ->assertOk();

    expect($sender->notifications()->count())->toBe(1)
        ->and($receiver->notifications()->count())->toBe(0);

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/friends/{$receiver->id}")
        ->assertOk();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated();

    expect($receiver->notifications()->count())->toBe(0);

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/accept")
        ->assertOk();

    expect($sender->notifications()->count())->toBe(1)
        ->and(FriendRequest::withTrashed()->count())->toBe(1);
});

it('skips a queued received notification once the request is no longer pending', function (): void {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();
    $notification = new FriendRequestReceivedNotification(
        $friendRequest->id,
        NotificationActor::fromUser($sender),
    );

    expect($notification->shouldSend($receiver, 'database'))->toBeTrue();

    $friendRequest->updateQuietly(['status' => FriendRequestStatus::Rejected]);

    expect($notification->shouldSend($receiver, 'database'))->toBeFalse()
        ->and($notification->shouldSend($sender, 'database'))->toBeFalse();
});

it('skips a queued accepted notification once the friendship is removed', function (): void {
    $sender = User::factory()->create();
    $receiver = User::factory()->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $notification = new FriendRequestAcceptedNotification(
        $friendRequest->id,
        NotificationActor::fromUser($receiver),
    );

    expect($notification->shouldSend($sender, 'database'))->toBeTrue();

    $friendRequest->delete();

    expect($notification->shouldSend($sender, 'database'))->toBeFalse()
        ->and($notification->shouldSend($receiver, 'database'))->toBeFalse();
});

it('requires authentication to view notifications', function (): void {
    $this->getJson('/api/v1/notifications')
        ->assertUnauthorized();
});

it('lists the authenticated user\'s notifications', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);
    $eve = User::factory()->create(['name' => 'Eve']);

    notifyPendingFriendRequest($user, $alice);
    notifyPendingFriendRequest($user, $bob);
    notifyPendingFriendRequest($other, $eve);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'type',
                        'message',
                        'data' => [
                            'friend_request_id',
                            'actor' => ['id', 'name', 'avatar_url'],
                        ],
                        'read_at',
                        'created_at',
                    ],
                ],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'has_next'],
            ],
        ]);
});

it('returns the unread notification count', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    notifyPendingFriendRequest($user, $alice);
    notifyPendingFriendRequest($user, $bob);

    $user->notifications()->first()->markAsRead();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/notifications/count')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.unread_count', 1);
});

it('marks one notification as read', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    notifyPendingFriendRequest($user, $alice);

    $notification = $user->notifications()->first();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($notification->fresh()->read_at)->not->toBeNull();
});

it('forbids marking another user\'s notification as read', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    notifyPendingFriendRequest($other, $alice);

    $notification = $other->notifications()->first();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/notifications/{$notification->id}/read")
        ->assertForbidden();

    expect($notification->fresh()->read_at)->toBeNull();
});

it('marks all notifications as read', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $alice = User::factory()->create(['name' => 'Alice']);
    $bob = User::factory()->create(['name' => 'Bob']);

    notifyPendingFriendRequest($user, $alice);
    notifyPendingFriendRequest($user, $bob);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/notifications/read-all')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.marked_read', 2);

    expect($user->unreadNotifications()->count())->toBe(0);
});
