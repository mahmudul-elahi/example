<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\ConversationParticipant;
use App\Models\FriendRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication for conversation endpoints', function (): void {
    $this->getJson('/api/v1/conversations')->assertUnauthorized();
    $this->postJson('/api/v1/conversations', ['recipient_id' => 1])->assertUnauthorized();
    $this->getJson('/api/v1/conversations/1')->assertUnauthorized();
    $this->deleteJson('/api/v1/conversations/1')->assertUnauthorized();
    $this->postJson('/api/v1/conversations/1/read')->assertUnauthorized();
});

it('rejects starting a conversation with yourself', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $user->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('rejects starting a conversation with a user who is not a friend', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $stranger->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('rejects starting a conversation if friend request is still pending', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($user)->to($target)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $target->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('rejects starting a conversation if friend was unfriended (soft deleted)', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $exFriend = User::factory()->withRole(Roles::User)->create();
    $request = FriendRequest::factory()->from($user)->to($exFriend)->accepted()->create();
    $request->delete(); // Unfriended

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $exFriend->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('rejects starting a conversation if either user blocked the other', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($user)->to($friend)->accepted()->create();

    // User blocked friend
    BlockedUser::factory()->blocker($user)->blocked($friend)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $friend->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');

    // Or friend blocked user
    BlockedUser::query()->truncate();
    BlockedUser::factory()->blocker($friend)->blocked($user)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $friend->id])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('recipient_id');
});

it('allows mutual friends to start a conversation', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($user)->to($friend)->accepted()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $friend->id])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.recipient.id', $friend->id);

    $this->assertDatabaseHas('conversations', [
        'id' => $response->json('data.id'),
        'user_one_id' => min($user->id, $friend->id),
        'user_two_id' => max($user->id, $friend->id),
    ]);

    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $response->json('data.id'),
        'user_id' => $user->id,
    ]);
    $this->assertDatabaseHas('conversation_participants', [
        'conversation_id' => $response->json('data.id'),
        'user_id' => $friend->id,
    ]);
});

it('never creates duplicate conversations between the same two users', function (): void {
    $userA = User::factory()->withRole(Roles::User)->create();
    $userB = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($userA)->to($userB)->accepted()->create();

    // User A starts conversation
    $res1 = $this->actingAs($userA, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $userB->id])
        ->assertCreated();

    $conversationId = $res1->json('data.id');

    // User A accidentally calls it again
    $res2 = $this->actingAs($userA, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $userB->id])
        ->assertCreated();

    expect($res2->json('data.id'))->toBe($conversationId);

    // User B also calls it from their side
    $res3 = $this->actingAs($userB, 'sanctum')
        ->postJson('/api/v1/conversations', ['recipient_id' => $userA->id])
        ->assertCreated();

    expect($res3->json('data.id'))->toBe($conversationId);

    expect(Conversation::query()->count())->toBe(1);
});

it('lists conversations for the authenticated user and excludes empty ones without messages', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $friend1 = User::factory()->withRole(Roles::User)->create();
    $friend2 = User::factory()->withRole(Roles::User)->create();

    // Conversation 1 has messages
    $conv1 = Conversation::factory()->between($user, $friend1)->create([
        'last_message_at' => now(),
    ]);
    Message::factory()->create([
        'conversation_id' => $conv1->id,
        'sender_id' => $friend1->id,
        'body' => 'Hello there',
    ]);

    // Conversation 2 has no messages yet (last_message_at is null)
    Conversation::factory()->between($user, $friend2)->create([
        'last_message_at' => null,
    ]);

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/conversations')
        ->assertOk()
        ->assertJsonPath('success', true);

    $items = $response->json('data.items');
    expect($items)->toHaveCount(1)
        ->and($items[0]['id'])->toBe($conv1->id)
        ->and($items[0]['recipient']['id'])->toBe($friend1->id)
        ->and($items[0]['last_message']['body'])->toBe('Hello there')
        ->and($items[0]['unread_count'])->toBe(1);
});

it('marks a conversation as read and resets unread count to 0', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($user, $friend)->create([
        'last_message_at' => now(),
    ]);
    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $friend->id,
        'created_at' => now()->subMinute(),
    ]);

    // Before reading: 1 unread
    $resBefore = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}")
        ->assertOk();
    expect($resBefore->json('data.unread_count'))->toBe(1);

    // Mark as read
    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/read")
        ->assertOk()
        ->assertJsonPath('success', true);

    // After reading: 0 unread
    $resAfter = $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}")
        ->assertOk();
    expect($resAfter->json('data.unread_count'))->toBe(0);
});

it('clears conversation for the deleting user only', function (): void {
    $userA = User::factory()->withRole(Roles::User)->create();
    $userB = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($userA, $userB)->create([
        'last_message_at' => now()->subMinutes(5),
    ]);
    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $userA->id,
        'created_at' => now()->subMinutes(5),
    ]);

    // User A deletes conversation ("delete for me")
    $this->actingAs($userA, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}")
        ->assertOk()
        ->assertJsonPath('success', true);

    // User A no longer sees it in their conversations list
    $this->actingAs($userA, 'sanctum')
        ->getJson('/api/v1/conversations')
        ->assertOk()
        ->assertJsonPath('data.items', []);

    // User B STILL sees the conversation
    $resB = $this->actingAs($userB, 'sanctum')
        ->getJson('/api/v1/conversations')
        ->assertOk();
    expect($resB->json('data.items'))->toHaveCount(1)
        ->and($resB->json('data.items.0.id'))->toBe($conv->id);

    // Check conversation_participants table
    $partA = ConversationParticipant::query()
        ->where('conversation_id', $conv->id)
        ->where('user_id', $userA->id)
        ->first();
    $partB = ConversationParticipant::query()
        ->where('conversation_id', $conv->id)
        ->where('user_id', $userB->id)
        ->first();

    expect($partA->cleared_at)->not->toBeNull()
        ->and($partB->cleared_at)->toBeNull();
});

it('returns 404 for non-numeric conversation and message identifiers', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/conversations/abc')
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/conversations/abc')
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/conversations/abc/read')
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/conversations/abc/messages')
        ->assertNotFound();

    $this->actingAs($user, 'sanctum')
        ->deleteJson('/api/v1/conversations/1/messages/abc')
        ->assertNotFound();
});

it('paginates conversations that share the same last message timestamp without losing items', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $timestamp = now()->subMinute()->startOfSecond();

    $conversationIds = collect(range(1, 3))
        ->map(fn (): int => Conversation::factory()
            ->between($user, User::factory()->withRole(Roles::User)->create())
            ->create(['last_message_at' => $timestamp])
            ->id)
        ->all();

    $firstPage = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/conversations?per_page=2')
        ->assertOk();

    $cursor = $firstPage->json('data.meta.next_cursor');
    expect($cursor)->toBeString();

    $secondPage = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/conversations?per_page=2&cursor='.urlencode((string) $cursor))
        ->assertOk();

    $returnedIds = collect($firstPage->json('data.items'))
        ->merge($secondPage->json('data.items'))
        ->pluck('id')
        ->all();

    expect($returnedIds)->toHaveCount(3)
        ->and(array_unique($returnedIds))->toHaveCount(3)
        ->and(array_values(array_intersect($conversationIds, $returnedIds)))->toHaveCount(3);
});

it('hides messages deleted by the user from previews and unread counts', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $conversation = Conversation::factory()->between($sender, $receiver)->create([
        'last_message_at' => now(),
    ]);
    $message = Message::factory()->create([
        'conversation_id' => $conversation->id,
        'sender_id' => $sender->id,
        'body' => 'Secret preview',
    ]);

    $this->actingAs($receiver, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conversation->id}/messages/{$message->id}")
        ->assertOk();

    $response = $this->actingAs($receiver, 'sanctum')
        ->getJson('/api/v1/conversations')
        ->assertOk();

    expect($response->json('data.items.0.last_message'))->toBeNull()
        ->and($response->json('data.items.0.unread_count'))->toBe(0);
});

it('forbids access to conversations the user is not part of', function (): void {
    $userA = User::factory()->withRole(Roles::User)->create();
    $userB = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();

    $conv = Conversation::factory()->between($userA, $userB)->create();

    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}")
        ->assertNotFound();

    $this->actingAs($stranger, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}")
        ->assertNotFound();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/read")
        ->assertNotFound();
});
