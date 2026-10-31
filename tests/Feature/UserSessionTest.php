<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('requires authentication to access sessions', function (): void {
    $this->getJson('/api/v1/profile/sessions')->assertUnauthorized();
    $this->deleteJson('/api/v1/profile/sessions')->assertUnauthorized();
    $this->deleteJson('/api/v1/profile/sessions/1')->assertUnauthorized();
});

it('lists all active sessions for the authenticated user and flags the current one', function (): void {
    $user = User::factory()->create();

    $token1 = $user->createToken('MacBook Pro');
    $token1->accessToken->forceFill(['ip_address' => '192.168.1.10'])->save();

    $token2 = $user->createToken('iPhone 15');
    $token2->accessToken->forceFill(['ip_address' => '10.0.0.5'])->save();

    $response = $this->withToken($token1->plainTextToken)
        ->getJson('/api/v1/profile/sessions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => ['id', 'device_name', 'ip_address', 'is_current', 'last_active_at', 'created_at'],
            ],
        ]);

    $items = $response->json('data');
    expect($items)->toHaveCount(2);

    $currentSession = collect($items)->firstWhere('is_current', true);
    expect($currentSession)->not->toBeNull()
        ->and($currentSession['id'])->toBe($token1->accessToken->id)
        ->and($currentSession['device_name'])->toBe('MacBook Pro')
        ->and($currentSession['ip_address'])->toBe('192.168.1.10');

    $otherSession = collect($items)->firstWhere('is_current', false);
    expect($otherSession)->not->toBeNull()
        ->and($otherSession['id'])->toBe($token2->accessToken->id)
        ->and($otherSession['device_name'])->toBe('iPhone 15');
});

it('revokes a specific session by id', function (): void {
    $user = User::factory()->create();

    $token1 = $user->createToken('Desktop');
    $token2 = $user->createToken('Tablet');

    expect($user->tokens()->count())->toBe(2);

    $this->withToken($token1->plainTextToken)
        ->deleteJson("/api/v1/profile/sessions/{$token2->accessToken->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Session revoked successfully');

    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->where('id', $token2->accessToken->id)->exists())->toBeFalse()
        ->and($user->tokens()->where('id', $token1->accessToken->id)->exists())->toBeTrue();
});

it('returns 404 when revoking another user session', function (): void {
    $user1 = User::factory()->create();
    $user2 = User::factory()->create();

    $token1 = $user1->createToken('User 1 Device');
    $token2 = $user2->createToken('User 2 Device');

    $this->withToken($token1->plainTextToken)
        ->deleteJson("/api/v1/profile/sessions/{$token2->accessToken->id}")
        ->assertNotFound();

    expect($user2->tokens()->where('id', $token2->accessToken->id)->exists())->toBeTrue();
});

it('revokes all other sessions while retaining the current session', function (): void {
    $user = User::factory()->create();

    $tokenCurrent = $user->createToken('Current Device');
    $tokenOther1 = $user->createToken('Other Device 1');
    $tokenOther2 = $user->createToken('Other Device 2');

    expect($user->tokens()->count())->toBe(3);

    $this->withToken($tokenCurrent->plainTextToken)
        ->deleteJson('/api/v1/profile/sessions')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Other sessions revoked successfully');

    expect($user->tokens()->count())->toBe(1)
        ->and($user->tokens()->where('id', $tokenCurrent->accessToken->id)->exists())->toBeTrue()
        ->and($user->tokens()->where('id', $tokenOther1->accessToken->id)->exists())->toBeFalse()
        ->and($user->tokens()->where('id', $tokenOther2->accessToken->id)->exists())->toBeFalse();
});
