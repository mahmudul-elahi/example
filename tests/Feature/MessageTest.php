<?php

declare(strict_types=1);

use App\Enums\MessageType;
use App\Enums\Roles;
use App\Models\BlockedUser;
use App\Models\Conversation;
use App\Models\FriendRequest;
use App\Models\Message;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires authentication for message endpoints', function (): void {
    $this->getJson('/api/v1/conversations/1/messages')->assertUnauthorized();
    $this->postJson('/api/v1/conversations/1/messages', ['type' => 'text', 'body' => 'hi'])->assertUnauthorized();
    $this->deleteJson('/api/v1/conversations/1/messages/1')->assertUnauthorized();
});

it('forbids non-participants from accessing messages of a conversation', function (): void {
    $userA = User::factory()->withRole(Roles::User)->create();
    $userB = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($userA, $userB)->create();

    $this->actingAs($stranger, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertNotFound();

    $this->actingAs($stranger, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", ['type' => 'text', 'body' => 'hi'])
        ->assertNotFound();
});

it('sends a text message between friends', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Hello friend!',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.type', 'text')
        ->assertJsonPath('data.body', 'Hello friend!')
        ->assertJsonPath('data.is_mine', true)
        ->assertJsonPath('data.sender.id', $sender->id)
        ->assertJsonPath('data.deleted_for_everyone', false);

    $this->assertDatabaseHas('messages', [
        'id' => $response->json('data.id'),
        'conversation_id' => $conv->id,
        'sender_id' => $sender->id,
        'type' => MessageType::Text->value,
        'body' => 'Hello friend!',
    ]);

    // Conversation last_message_at was updated
    expect($conv->fresh()->last_message_at)->not->toBeNull();
});

it('rejects sending a message if the users are not friends', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($sender, $stranger)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Are you there?',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('rejects sending a message if friends unfriended each other', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $friendship = FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $friendship->delete(); // Unfriended!

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Still friends?',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('rejects sending a message if either user has blocked the other', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    BlockedUser::factory()->blocker($receiver)->blocked($sender)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Hello?',
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('sends an image message and uploads the file', function (): void {
    Storage::fake();

    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $file = UploadedFile::fake()->image('vacation.jpg', 600, 400);

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'image',
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'image')
        ->assertJsonPath('data.attachment_mime', 'image/jpeg');

    expect($response->json('data.attachment_url'))->not->toBeNull();

    $message = Message::query()->findOrFail($response->json('data.id'));
    expect($message->attachment_path)->not->toBeNull();
    Storage::assertExists($message->attachment_path);
});

it('sends an audio voice message with duration', function (): void {
    Storage::fake();

    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $file = UploadedFile::fake()->create('voice.mp3', 250, 'audio/mpeg');

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'audio',
            'file' => $file,
            'audio_duration' => 24,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'audio')
        ->assertJsonPath('data.audio_duration', 24);

    $message = Message::query()->findOrFail($response->json('data.id'));
    Storage::assertExists($message->attachment_path);
});

it('sends a generic document attachment', function (): void {
    Storage::fake();

    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $file = UploadedFile::fake()->create('contract.pdf', 1000, 'application/pdf');

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'file',
            'file' => $file,
        ])
        ->assertCreated()
        ->assertJsonPath('data.type', 'file')
        ->assertJsonPath('data.attachment_mime', 'application/pdf');

    $message = Message::query()->findOrFail($response->json('data.id'));
    Storage::assertExists($message->attachment_path);
});

it('lists messages with cursor pagination and is_mine flag', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $sender->id,
        'body' => 'Message 1 from sender',
    ]);
    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $receiver->id,
        'body' => 'Message 2 from receiver',
    ]);

    $response = $this->actingAs($sender, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk()
        ->assertJsonPath('success', true);

    $items = $response->json('data.items');
    expect($items)->toHaveCount(2);

    // Latest message is first
    expect($items[0]['body'])->toBe('Message 2 from receiver')
        ->and($items[0]['is_mine'])->toBeFalse()
        ->and($items[1]['body'])->toBe('Message 1 from sender')
        ->and($items[1]['is_mine'])->toBeTrue();
});

it('allows a user to delete a message for themselves only', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $msg = Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $sender->id,
        'body' => 'Secret note',
    ]);

    // Receiver deletes it for themselves
    $this->actingAs($receiver, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}/messages/{$msg->id}", [
            'for_everyone' => false,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('message_deletions', [
        'message_id' => $msg->id,
        'user_id' => $receiver->id,
    ]);

    // Receiver cannot see the message anymore
    $resReceiver = $this->actingAs($receiver, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resReceiver->json('data.items'))->toBeEmpty();

    // Sender STILL sees the message completely intact
    $resSender = $this->actingAs($sender, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resSender->json('data.items'))->toHaveCount(1)
        ->and($resSender->json('data.items.0.body'))->toBe('Secret note');
});

it('allows the sender to delete a message for everyone', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $msg = Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $sender->id,
        'body' => 'I made a typo here',
    ]);

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}/messages/{$msg->id}", [
            'for_everyone' => true,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    // Both sender and receiver see "This message was deleted"
    $resSender = $this->actingAs($sender, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resSender->json('data.items.0.deleted_for_everyone'))->toBeTrue()
        ->and($resSender->json('data.items.0.body'))->toBe('This message was deleted');

    $resReceiver = $this->actingAs($receiver, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resReceiver->json('data.items.0.deleted_for_everyone'))->toBeTrue()
        ->and($resReceiver->json('data.items.0.body'))->toBe('This message was deleted');
});

it('rejects delete for everyone if caller is not the sender', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $msg = Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $sender->id,
        'body' => 'My message',
    ]);

    $this->actingAs($receiver, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}/messages/{$msg->id}", [
            'for_everyone' => true,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');
});

it('rejects a text message with an attachment', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'text',
            'body' => 'Hello',
            'file' => UploadedFile::fake()->image('photo.jpg'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('requires an audio duration for audio messages', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'audio',
            'file' => UploadedFile::fake()->create('voice.mp3', 250, 'audio/mpeg'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('audio_duration');
});

it('rejects an audio duration for non-audio messages', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'image',
            'file' => UploadedFile::fake()->image('photo.jpg'),
            'audio_duration' => 10,
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('audio_duration');
});

it('rejects active file types for generic attachments', function (): void {
    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'file',
            'file' => UploadedFile::fake()->create('page.html', 100, 'text/html'),
        ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('file');
});

it('deletes the attachment file when a message is deleted for everyone', function (): void {
    Storage::fake();

    $sender = User::factory()->withRole(Roles::User)->create();
    $receiver = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($sender)->to($receiver)->accepted()->create();
    $conv = Conversation::factory()->between($sender, $receiver)->create();

    $response = $this->actingAs($sender, 'sanctum')
        ->postJson("/api/v1/conversations/{$conv->id}/messages", [
            'type' => 'image',
            'file' => UploadedFile::fake()->image('vacation.jpg'),
        ])
        ->assertCreated();

    $message = Message::query()->findOrFail($response->json('data.id'));
    $attachmentPath = (string) $message->attachment_path;

    Storage::assertExists($attachmentPath);

    $this->actingAs($sender, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}/messages/{$message->id}", [
            'for_everyone' => true,
        ])
        ->assertOk();

    Storage::assertMissing($attachmentPath);
    expect($message->fresh()->attachment_path)->toBeNull();
});

it('hides messages sent prior to clearing the conversation', function (): void {
    $userA = User::factory()->withRole(Roles::User)->create();
    $userB = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($userA)->to($userB)->accepted()->create();
    $conv = Conversation::factory()->between($userA, $userB)->create();

    // Message 1 (old)
    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $userA->id,
        'body' => 'Old message',
        'created_at' => now()->subMinutes(10),
    ]);

    // User A clears conversation
    $this->actingAs($userA, 'sanctum')
        ->deleteJson("/api/v1/conversations/{$conv->id}")
        ->assertOk();

    // Message 2 (new, after cleared_at)
    Message::factory()->create([
        'conversation_id' => $conv->id,
        'sender_id' => $userB->id,
        'body' => 'New message after clear',
        'created_at' => now()->addMinute(),
    ]);

    // User A should only see the new message
    $resA = $this->actingAs($userA, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resA->json('data.items'))->toHaveCount(1)
        ->and($resA->json('data.items.0.body'))->toBe('New message after clear');

    // User B should see BOTH messages
    $resB = $this->actingAs($userB, 'sanctum')
        ->getJson("/api/v1/conversations/{$conv->id}/messages")
        ->assertOk();
    expect($resB->json('data.items'))->toHaveCount(2);
});
