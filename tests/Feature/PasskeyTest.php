<?php

declare(strict_types=1);

use App\Models\User;
use Database\Factories\PasskeyFactory;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Laravel\Passkeys\Passkey;

uses(RefreshDatabase::class);

it('generates passkey registration options for authenticated user', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user)
        ->getJson('/api/v1/auth/passkeys/register/options');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Passkey creation options generated')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'challenge',
                'rp',
                'user',
                'pubKeyCredParams',
            ],
        ]);

    expect(Cache::has('passkey:reg:'.$user->id))->toBeTrue();
});

it('rejects registration options request for guests', function (): void {
    $this->getJson('/api/v1/auth/passkeys/register/options')
        ->assertUnauthorized();
});

it('rejects passkey registration verification when challenge is expired or missing', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->postJson('/api/v1/auth/passkeys/register/verify', [
            'name' => 'My Device',
            'credential' => [
                'id' => 'dummy-id',
                'rawId' => 'dummy-raw-id',
                'type' => 'public-key',
                'response' => [
                    'clientDataJSON' => 'test',
                    'attestationObject' => 'test',
                ],
            ],
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('credential');
});

it('generates passkey login options for guest', function (): void {
    $response = $this->postJson('/api/v1/auth/passkeys/login/options');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Passkey login options generated')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'challenge_token',
                'options' => [
                    'challenge',
                    'rpId',
                ],
            ],
        ]);

    $challengeToken = (string) $response->json('data.challenge_token');
    expect(Cache::has('passkey:login:'.$challengeToken))->toBeTrue();
});

it('generates passkey login options with targeted user email', function (): void {
    $user = User::factory()->create();
    PasskeyFactory::new()->create(['user_id' => $user->id]);

    $response = $this->postJson('/api/v1/auth/passkeys/login/options', [
        'email' => $user->email,
    ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'challenge_token',
                'options' => [
                    'challenge',
                    'allowCredentials',
                ],
            ],
        ]);
});

it('rejects passkey login verification with missing or expired challenge token', function (): void {
    $this->postJson('/api/v1/auth/passkeys/login/verify', [
        'challenge_token' => 'expired-token',
        'credential' => [
            'id' => 'dummy-id',
            'rawId' => 'dummy-raw-id',
            'type' => 'public-key',
            'response' => [
                'clientDataJSON' => 'test',
                'authenticatorData' => 'test',
                'signature' => 'test',
            ],
        ],
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('credential');
});

it('lists all passkeys for the authenticated user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();

    PasskeyFactory::new()->count(2)->create(['user_id' => $user->id]);
    PasskeyFactory::new()->create(['user_id' => $otherUser->id]);

    $response = $this->actingAs($user)
        ->getJson('/api/v1/auth/passkeys');

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonCount(2, 'data')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'authenticator',
                    'last_used_at',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
});

it('renames a passkey owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $passkey = PasskeyFactory::new()->create([
        'user_id' => $user->id,
        'name' => 'Original Name',
    ]);

    $response = $this->actingAs($user)
        ->patchJson("/api/v1/auth/passkeys/{$passkey->id}", [
            'name' => 'Updated Name',
        ]);

    $response->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Updated Name');

    expect($passkey->fresh()->name)->toBe('Updated Name');
});

it('prevents renaming a passkey owned by another user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey = PasskeyFactory::new()->create([
        'user_id' => $otherUser->id,
        'name' => 'Other Passkey',
    ]);

    $this->actingAs($user)
        ->patchJson("/api/v1/auth/passkeys/{$passkey->id}", [
            'name' => 'Hacked Name',
        ])
        ->assertNotFound();

    expect($passkey->fresh()->name)->toBe('Other Passkey');
});

it('deletes a passkey owned by the authenticated user', function (): void {
    $user = User::factory()->create();
    $passkey = PasskeyFactory::new()->create(['user_id' => $user->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/auth/passkeys/{$passkey->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Passkey deleted successfully');

    expect(Passkey::find($passkey->id))->toBeNull();
});

it('prevents deleting a passkey owned by another user', function (): void {
    $user = User::factory()->create();
    $otherUser = User::factory()->create();
    $passkey = PasskeyFactory::new()->create(['user_id' => $otherUser->id]);

    $this->actingAs($user)
        ->deleteJson("/api/v1/auth/passkeys/{$passkey->id}")
        ->assertNotFound();

    expect(Passkey::find($passkey->id))->not->toBeNull();
});
