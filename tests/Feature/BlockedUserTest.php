<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\BlockedUser;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to list blocked users', function (): void {
    $this->getJson('/api/v1/users/blocked')
        ->assertUnauthorized();
});

it('requires authentication to block a user', function (): void {
    $target = User::factory()->create();

    $this->postJson("/api/v1/users/{$target->id}/block")
        ->assertUnauthorized();
});

it('requires authentication to unblock a user', function (): void {
    $target = User::factory()->create();

    $this->deleteJson("/api/v1/users/{$target->id}/block")
        ->assertUnauthorized();
});

it('allows an authenticated user to block another user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$target->id}/block")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User blocked successfully');

    $this->assertDatabaseHas('blocked_users', [
        'blocker_id' => $user->id,
        'blocked_id' => $target->id,
    ]);
});

it('is idempotent when blocking the same user again', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();
    BlockedUser::factory()->blocker($user)->blocked($target)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$target->id}/block")
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(BlockedUser::query()->where('blocker_id', $user->id)->count())->toBe(1);
});

it('rejects blocking yourself', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson("/api/v1/users/{$user->id}/block")
        ->assertUnprocessable()
        ->assertJsonValidationErrors('user_id');
});

it('returns 404 when blocking a non-existent user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/users/999999/block')
        ->assertNotFound();
});

it('allows an authenticated user to unblock a blocked user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $target = User::factory()->withRole(Roles::User)->create();
    BlockedUser::factory()->blocker($user)->blocked($target)->create();

    $this->actingAs($user, 'sanctum')
        ->deleteJson("/api/v1/users/{$target->id}/block")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User unblocked successfully');

    $this->assertDatabaseMissing('blocked_users', [
        'blocker_id' => $user->id,
        'blocked_id' => $target->id,
    ]);
});

it('lists blocked users for the authenticated user', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();
    $blocked1 = User::factory()->withRole(Roles::User)->create();
    $blocked2 = User::factory()->withRole(Roles::User)->create();
    $otherUser = User::factory()->withRole(Roles::User)->create();

    BlockedUser::factory()->blocker($user)->blocked($blocked1)->create();
    BlockedUser::factory()->blocker($user)->blocked($blocked2)->create();
    // otherUser blocked someone else
    BlockedUser::factory()->blocker($otherUser)->blocked($user)->create();

    $response = $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/users/blocked')
        ->assertOk()
        ->assertJsonPath('success', true);

    $ids = collect($response->json('data.items'))->pluck('blocked_user.id')->all();
    expect($ids)->toContain($blocked1->id, $blocked2->id)
        ->and($ids)->not->toContain($user->id);
});
