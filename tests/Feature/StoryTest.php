<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Enums\StoryMediaType;
use App\Models\BlockedUser;
use App\Models\FriendRequest;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('requires authentication for story endpoints', function (): void {
    $this->getJson('/api/v1/stories')->assertUnauthorized();
    $this->getJson('/api/v1/stories/mine')->assertUnauthorized();
    $this->postJson('/api/v1/stories')->assertUnauthorized();
    $this->getJson('/api/v1/stories/1/media')->assertUnauthorized();
    $this->deleteJson('/api/v1/stories/1')->assertUnauthorized();
});

it('creates multiple image and video stories that each expire after 24 hours', function (): void {
    Storage::fake();
    $this->travelTo('2026-10-26 10:00:00');
    $user = User::factory()->withRole(Roles::User)->create();

    $imageResponse = $this->actingAs($user, 'sanctum')
        ->post('/api/v1/stories', [
            'media' => UploadedFile::fake()->image('story.jpg'),
            'caption' => 'Morning walk',
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.caption', 'Morning walk')
        ->assertJsonPath('data.media_type', StoryMediaType::Image->value);

    $videoResponse = $this->post('/api/v1/stories', [
        'media' => UploadedFile::fake()->create('story.mp4', 512, 'video/mp4'),
    ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('data.caption', null)
        ->assertJsonPath('data.media_type', StoryMediaType::Video->value);

    $imageStory = Story::query()->findOrFail($imageResponse->json('data.id'));
    $videoStory = Story::query()->findOrFail($videoResponse->json('data.id'));

    expect($imageStory->expires_at->timestamp - $imageStory->created_at->timestamp)->toBe(86400)
        ->and($videoStory->expires_at->timestamp - $videoStory->created_at->timestamp)->toBe(86400);
    $this->assertDatabaseCount('stories', 2);
    Storage::assertExists([$imageStory->media_path, $videoStory->media_path]);
});

it('rejects unsupported story uploads', function (): void {
    Storage::fake();
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->post('/api/v1/stories', [
            'media' => UploadedFile::fake()->create('document.pdf', 100, 'application/pdf'),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('media');

    $this->assertDatabaseCount('stories', 0);
    Storage::assertDirectoryEmpty('stories/'.$user->id);
});

it('lists only active stories from friends who have not blocked each other', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    $blockedByViewer = User::factory()->withRole(Roles::User)->create();
    $blockedViewer = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();

    FriendRequest::factory()->from($viewer)->to($friend)->accepted()->create();
    FriendRequest::factory()->from($viewer)->to($blockedByViewer)->accepted()->create();
    FriendRequest::factory()->from($blockedViewer)->to($viewer)->accepted()->create();
    BlockedUser::factory()->blocker($viewer)->blocked($blockedByViewer)->create();
    BlockedUser::factory()->blocker($blockedViewer)->blocked($viewer)->create();

    $visibleStory = Story::factory()->for($friend)->create();
    $expiredStory = Story::factory()->for($friend)->create(['expires_at' => now()->subSecond()]);
    $blockedStory = Story::factory()->for($blockedByViewer)->create();
    Story::factory()->for($blockedViewer)->create();
    Story::factory()->for($stranger)->create();

    $response = $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/stories')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => ['id', 'caption', 'media_type', 'media_url', 'expires_at', 'created_at', 'author'],
                ],
                'meta' => ['per_page', 'next_cursor', 'prev_cursor', 'has_next'],
            ],
        ]);

    $storyIds = collect($response->json('data.items'))->pluck('id')->all();

    expect($storyIds)
        ->toContain($visibleStory->id)
        ->not->toContain($expiredStory->id)
        ->not->toContain($blockedStory->id);
});

it('lists only the authenticated users active stories', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    $ownStory = Story::factory()->for($user)->create();
    $expiredStory = Story::factory()->for($user)->create(['expires_at' => now()->subSecond()]);
    Story::factory()->for($friend)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/stories/mine')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(collect($response->json('data.items'))->pluck('id')->all())
        ->toBe([$ownStory->id]);
    expect($expiredStory->id)->not->toBe($ownStory->id);
});

it('serves story media only to active friends', function (): void {
    Storage::fake();
    $viewer = User::factory()->withRole(Roles::User)->create();
    $friend = User::factory()->withRole(Roles::User)->create();
    $blockedFriend = User::factory()->withRole(Roles::User)->create();
    $stranger = User::factory()->withRole(Roles::User)->create();
    FriendRequest::factory()->from($viewer)->to($friend)->accepted()->create();
    FriendRequest::factory()->from($viewer)->to($blockedFriend)->accepted()->create();
    BlockedUser::factory()->blocker($viewer)->blocked($blockedFriend)->create();
    $story = Story::factory()->for($friend)->create();
    $blockedStory = Story::factory()->for($blockedFriend)->create();
    $expiredStory = Story::factory()->for($friend)->create(['expires_at' => now()->subSecond()]);
    Storage::put($story->media_path, 'story bytes');
    Storage::put($blockedStory->media_path, 'blocked bytes');
    Storage::put($expiredStory->media_path, 'expired bytes');

    $this->actingAs($viewer, 'sanctum')
        ->get('/api/v1/stories/'.$story->id.'/media')
        ->assertOk()
        ->assertHeader('Content-Type', 'image/jpeg');

    $this->actingAs($stranger, 'sanctum')
        ->getJson('/api/v1/stories/'.$story->id.'/media')
        ->assertForbidden();

    $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/stories/'.$blockedStory->id.'/media')
        ->assertForbidden();

    $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/stories/'.$expiredStory->id.'/media')
        ->assertForbidden();
});

it('deletes the owners story and its media but forbids other users', function (): void {
    Storage::fake();
    $owner = User::factory()->withRole(Roles::User)->create();
    $otherUser = User::factory()->withRole(Roles::User)->create();
    $story = Story::factory()->for($owner)->create();
    Storage::put($story->media_path, 'story bytes');

    $this->actingAs($otherUser, 'sanctum')
        ->deleteJson('/api/v1/stories/'.$story->id)
        ->assertForbidden();

    $this->actingAs($owner, 'sanctum')
        ->deleteJson('/api/v1/stories/'.$story->id)
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data', null);

    $this->assertDatabaseMissing('stories', ['id' => $story->id]);
    Storage::assertMissing($story->media_path);
});

it('removes expired stories and their media during pruning', function (): void {
    Storage::fake();
    $user = User::factory()->withRole(Roles::User)->create();
    $expiredStory = Story::factory()->for($user)->create(['expires_at' => now()->subSecond()]);
    $activeStory = Story::factory()->for($user)->create();
    Storage::put($expiredStory->media_path, 'expired bytes');
    Storage::put($activeStory->media_path, 'active bytes');

    $this->artisan('model:prune', ['--model' => [Story::class]])->assertSuccessful();

    $this->assertDatabaseMissing('stories', ['id' => $expiredStory->id]);
    $this->assertDatabaseHas('stories', ['id' => $activeStory->id]);
    Storage::assertMissing($expiredStory->media_path);
    Storage::assertExists($activeStory->media_path);
});
