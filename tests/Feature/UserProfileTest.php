<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlockedUser;
use App\Models\Comment;
use App\Models\Like;
use App\Models\PetMemorial;
use App\Models\Story;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('returns user badge on auth/me without stats counts', function (): void {
    $user = User::factory()->withRole(Roles::User)->create([
        'username' => 'pet_lover',
    ]);

    PetMemorial::factory()->for($user)->count(2)->create();
    PetMemorial::factory()->for($user)->draft()->create();
    PetMemorial::factory()->for($user)->private()->create();

    $memorial = PetMemorial::factory()->create();
    Comment::factory()->for($user)->create([
        'commentable_type' => PetMemorial::class,
        'commentable_id' => $memorial->id,
    ]);
    Like::factory()->for($user)->create([
        'likeable_type' => PetMemorial::class,
        'likeable_id' => $memorial->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.username', 'pet_lover')
        ->assertJsonPath('data.badge', 'Pet Guardian')
        ->assertJsonMissingPath('data.memorials_count')
        ->assertJsonMissingPath('data.comments_count')
        ->assertJsonMissingPath('data.likes_count');
});

it('returns user stats counts on profile/stats', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    PetMemorial::factory()->for($user)->count(2)->create();
    PetMemorial::factory()->for($user)->draft()->create();
    PetMemorial::factory()->for($user)->private()->create();

    $memorial = PetMemorial::factory()->create();
    Comment::factory()->for($user)->create([
        'commentable_type' => PetMemorial::class,
        'commentable_id' => $memorial->id,
    ]);
    Like::factory()->for($user)->create([
        'likeable_type' => PetMemorial::class,
        'likeable_id' => $memorial->id,
    ]);

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/profile/stats')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User stats retrieved successfully')
        ->assertJsonPath('data.memorials_count', 4)
        ->assertJsonPath('data.comments_count', 1)
        ->assertJsonPath('data.likes_count', 1);
});

it('requires authentication to view profile stats', function (): void {
    $this->getJson('/api/v1/profile/stats')->assertUnauthorized();
});

it('returns Administrator badge for admin user on auth/me', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.badge', 'Administrator');
});

it('requires authentication to view user profiles', function (): void {
    $target = User::factory()->withRole(Roles::User)->create();

    $this->getJson("/api/v1/users/{$target->id}")
        ->assertUnauthorized();

    $this->getJson("/api/v1/users/{$target->id}/memorials")
        ->assertUnauthorized();

    $this->getJson("/api/v1/users/{$target->id}/stories")
        ->assertUnauthorized();
});

it('shows another user profile by numeric id', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create([
        'name' => 'Sarah Connor',
        'username' => 'sarah_c',
    ]);

    PetMemorial::factory()->for($target)->count(2)->create();
    PetMemorial::factory()->for($target)->draft()->create();

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $target->id)
        ->assertJsonPath('data.name', 'Sarah Connor')
        ->assertJsonPath('data.username', 'sarah_c')
        ->assertJsonPath('data.badge', 'Pet Guardian')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'username',
                'email',
                'avatar',
                'role',
                'badge',
                'email_verified',
                'created_at',
                'updated_at',
            ],
        ]);
});

it('resolves user profile by username and with @ prefix', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create([
        'username' => 'unique_user',
    ]);

    $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/users/unique_user')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $target->id);

    $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/users/@unique_user')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $target->id);
});

it('returns 404 when viewing a blocked user profile', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();

    BlockedUser::factory()->blocker($viewer)->blocked($target)->create();

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}")
        ->assertNotFound();

    $this->actingAs($target, 'sanctum')
        ->getJson("/api/v1/users/{$viewer->id}")
        ->assertNotFound();
});

it('returns 404 when user is not found', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($viewer, 'sanctum')
        ->getJson('/api/v1/users/99999999')
        ->assertNotFound();
});

it('lists only publicly visible memorials for another user', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();

    $publicMemorial = PetMemorial::factory()->for($target)->create(['pet_name' => 'Buddy']);
    PetMemorial::factory()->for($target)->draft()->create(['pet_name' => 'Draft Pet']);
    PetMemorial::factory()->for($target)->private()->create(['pet_name' => 'Secret Pet']);

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}/memorials")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.items.0.id', $publicMemorial->id)
        ->assertJsonCount(1, 'data.items');
});

it('lists all memorials when viewing own user memorials or when admin', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    PetMemorial::factory()->for($user)->create();
    PetMemorial::factory()->for($user)->draft()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson("/api/v1/users/{$user->id}/memorials")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data.items');

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/users/{$user->id}/memorials")
        ->assertOk()
        ->assertJsonCount(2, 'data.items');
});

it('returns 404 for memorials when users are blocked', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();
    BlockedUser::factory()->blocker($viewer)->blocked($target)->create();

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}/memorials")
        ->assertNotFound();
});

it('lists only active stories for a user and excludes expired stories', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();

    $activeStory = Story::factory()->forUser($target)->image()->create([
        'caption' => 'Active story',
        'expires_at' => now()->addHours(12),
    ]);
    Story::factory()->forUser($target)->image()->expired()->create([
        'caption' => 'Expired story',
    ]);

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}/stories")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(1, 'data.items')
        ->assertJsonPath('data.items.0.id', $activeStory->id)
        ->assertJsonPath('data.items.0.caption', 'Active story');
});

it('returns 404 for stories when users are blocked', function (): void {
    $viewer = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();
    BlockedUser::factory()->blocker($target)->blocked($viewer)->create();

    $this->actingAs($viewer, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}/stories")
        ->assertNotFound();
});

it('requires authentication to update profile image', function (): void {
    $this->postJson('/api/v1/profile/avatar', [
        'avatar' => UploadedFile::fake()->image('avatar.jpg'),
    ])->assertUnauthorized();
});

it('updates user profile image via POST profile/avatar', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('my-avatar.jpg'),
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Profile image updated successfully')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'id',
                'name',
                'username',
                'email',
                'avatar',
                'role',
            ],
        ]);

    $freshUser = $user->fresh();
    expect($freshUser->avatar)->not->toBeNull();
    Storage::assertExists($freshUser->avatar);
    $response->assertJsonPath('data.avatar', Storage::url($freshUser->avatar));
});

it('replaces the previous avatar file when updated', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->withAvatar('avatars/old-pic.jpg')->create();
    Storage::put('avatars/old-pic.jpg', 'old-content');

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('new-pic.jpg'),
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $freshUser = $user->fresh();
    expect($freshUser->avatar)->not->toBe('avatars/old-pic.jpg');
    Storage::assertMissing('avatars/old-pic.jpg');
    Storage::assertExists($freshUser->avatar);
});

it('rejects an avatar that is not an image file', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->create('document.pdf', 10),
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('rejects when no avatar is provided', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/avatar', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('only updates avatar and ignores any attempts to change name, email, or role', function (): void {
    Storage::fake();

    $user = User::factory()->withRole(Roles::User)->create([
        'name' => 'Original Name',
        'email' => 'original@example.com',
    ]);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/avatar', [
            'avatar' => UploadedFile::fake()->image('pic.jpg'),
            'name' => 'Hacked Name',
            'email' => 'hacked@example.com',
            'role' => Roles::Admin->value,
        ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $freshUser = $user->fresh();
    expect($freshUser->name)->toBe('Original Name')
        ->and($freshUser->email)->toBe('original@example.com')
        ->and($freshUser->isAdmin())->toBeFalse()
        ->and($freshUser->avatar)->not->toBeNull();
});
