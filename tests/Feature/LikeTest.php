<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\PetMemorial;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns 401 when no token is provided', function (): void {
    $memorial = PetMemorial::factory()->create();

    $this->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertUnauthorized();
});

it('lets a user like a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.likes_count', 1);

    $this->assertDatabaseHas('likes', [
        'user_id' => $user->id,
        'likeable_type' => PetMemorial::class,
        'likeable_id' => $memorial->id,
    ]);
});

it('does not duplicate the like when a user likes twice', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 1);

    expect($memorial->likes()->count())->toBe(1);
});

it('lets a user unlike a memorial', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->create();
    $memorial->likes()->create(['user_id' => $user->id]);

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertOk()
        ->assertJsonPath('data.likes_count', 0);

    expect($memorial->likes()->count())->toBe(0);
});

it('forbids a user from liking a private memorial they cannot view', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $memorial = PetMemorial::factory()->private()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/memorials/{$memorial->slug}/like")
        ->assertForbidden();

    expect($memorial->likes()->count())->toBe(0);
});
