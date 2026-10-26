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

uses(RefreshDatabase::class);

it('returns profile counts and badge for the authenticated user on auth/me', function (): void {
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
        ->assertJsonPath('data.memorials_count', 4)
        ->assertJsonPath('data.comments_count', 1)
        ->assertJsonPath('data.likes_count', 1);
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
        ->assertJsonPath('data.memorials_count', 2)
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
                'memorials_count',
                'likes_count',
                'comments_count',
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

it('lets an admin viewer see all memorials count for another user', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $target = User::factory()->withRole(Roles::User)->create();

    PetMemorial::factory()->for($target)->count(2)->create();
    PetMemorial::factory()->for($target)->draft()->create();
    PetMemorial::factory()->for($target)->private()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/users/{$target->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.memorials_count', 4);
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
