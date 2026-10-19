<?php

declare(strict_types=1);

use App\Enums\FriendRequestStatus;
use App\Enums\Roles;
use App\Models\FriendRequest;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to send a friend request', function (): void {
    $receiver = User::factory()->create();

    $this->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertUnauthorized();
});

it('requires authentication to list friend requests', function (): void {
    $this->getJson('/api/v1/friend-requests')
        ->assertUnauthorized();
});

it('sends a friend request to another user', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'pending')
        ->assertJsonPath('data.sender.id', $sender->id)
        ->assertJsonPath('data.receiver.id', $receiver->id);

    $this->assertDatabaseHas('friend_requests', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'status' => FriendRequestStatus::Pending->value,
    ]);
});

it('rejects a friend request sent to yourself', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $user->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('rejects a friend request to a user that does not exist', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => 999999])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('rejects a request when one is already pending in either direction', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');

    $this->actingAs($receiver, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $sender->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('rejects a request to an existing friend', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('reuses a rejected request when the sender tries again', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->rejected()->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson('/api/v1/friend-requests', ['user_id' => $receiver->id])
        ->assertCreated()
        ->assertJsonPath('data.status', 'pending');

    expect(FriendRequest::query()->count())->toBe(1);

    $this->assertDatabaseHas('friend_requests', [
        'sender_id' => $sender->id,
        'receiver_id' => $receiver->id,
        'status' => FriendRequestStatus::Pending->value,
        'responded_at' => null,
    ]);
});

it('lets the receiver accept a friend request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/accept")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'accepted');

    $friendRequest->refresh();

    expect($friendRequest->status)->toBe(FriendRequestStatus::Accepted)
        ->and($friendRequest->responded_at)->not->toBeNull();
});

it('lets the receiver reject a friend request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($receiver, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/reject")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.status', 'rejected');

    expect($friendRequest->refresh()->status)->toBe(FriendRequestStatus::Rejected);
});

it('forbids the sender from responding to their own request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/friend-requests/{$friendRequest->id}/accept")
        ->assertForbidden();

    expect($friendRequest->refresh()->status)->toBe(FriendRequestStatus::Pending);
});

it('lets the sender cancel a pending request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/friend-requests/{$friendRequest->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    $this->assertDatabaseMissing('friend_requests', ['id' => $friendRequest->id]);
});

it('forbids the receiver from cancelling a request', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendRequest = FriendRequest::factory()->from($sender)->to($receiver)->create();

    $this->actingAs($receiver, 'sanctum')
        ->deleteJson("/api/v1/friend-requests/{$friendRequest->id}")
        ->assertForbidden();

    $this->assertDatabaseHas('friend_requests', ['id' => $friendRequest->id]);
});

it('lists received requests with the pending count', function (): void {
    $receiver = User::factory()->withRole(Roles::User)->create();
    $other = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->to($receiver)->count(2)->create();
    FriendRequest::factory()->from($receiver)->to($other)->create();

    $this->actingAs($receiver, 'sanctum')
        ->getJson('/api/v1/friend-requests')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.pending_count', 2)
        ->assertJsonPath('data.meta.per_page', 15)
        ->assertJsonCount(2, 'data.items')
        ->assertJsonStructure([
            'data' => [
                'items' => [
                    '*' => ['id', 'status', 'sender' => ['id', 'name'], 'receiver' => ['id', 'name']],
                ],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'pending_count'],
            ],
        ]);
});

it('paginates received requests with a cursor', function (): void {
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->to($receiver)->count(3)->create();

    $firstPage = $this->actingAs($receiver, 'sanctum')
        ->getJson('/api/v1/friend-requests?per_page=1')
        ->assertOk()
        ->assertJsonCount(1, 'data.items');

    $cursor = $firstPage->json('data.meta.next_cursor');

    expect($cursor)->not->toBeNull();

    $secondPage = $this->actingAs($receiver, 'sanctum')
        ->getJson('/api/v1/friend-requests?per_page=1&cursor='.urlencode((string) $cursor))
        ->assertOk()
        ->assertJsonCount(1, 'data.items');

    expect($secondPage->json('data.items.0.id'))->not->toBe($firstPage->json('data.items.0.id'));
});

it('returns the pending request count', function (): void {
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->to($receiver)->count(3)->create();

    $this->actingAs($receiver, 'sanctum')
        ->getJson('/api/v1/friend-requests/count')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.pending_count', 3);
});

it('lists only accepted friends', function (): void {
    $me = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create(['name' => 'Friend One']);
    $incoming = User::factory()->withRole(Roles::User)->create(['name' => 'Friend Two']);
    $pending = User::factory()->withRole(Roles::User)->create();

    FriendRequest::factory()->from($me)->to($friend)->accepted()->create();
    FriendRequest::factory()->from($incoming)->to($me)->accepted()->create();
    FriendRequest::factory()->from($me)->to($pending)->create();

    $this->actingAs($me, 'sanctum')
        ->getJson('/api/v1/friends')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.per_page', 15)
        ->assertJsonCount(2, 'data.items');
});
