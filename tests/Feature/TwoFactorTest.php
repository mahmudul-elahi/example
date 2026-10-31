<?php

declare(strict_types=1);

use App\Models\User;
use App\Services\TwoFactorServiceInterface;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PragmaRX\Google2FA\Google2FA;

uses(RefreshDatabase::class);

it('requires authentication to manage two factor authentication', function (): void {
    $this->postJson('/api/v1/profile/two-factor/enable')->assertUnauthorized();
    $this->postJson('/api/v1/profile/two-factor/confirm', ['code' => '123456'])->assertUnauthorized();
    $this->postJson('/api/v1/profile/two-factor/disable', ['password' => 'password'])->assertUnauthorized();
    $this->postJson('/api/v1/profile/two-factor/recovery-codes')->assertUnauthorized();
});

it('initiates two factor authentication and returns secret and qr url', function (): void {
    $user = User::factory()->create();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/enable')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['secret', 'qr_code_url'],
        ]);

    $user->refresh();
    expect($user->two_factor_secret)->not->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});

it('confirms two factor authentication with a valid TOTP code', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/enable')
        ->assertOk();

    $user->refresh();
    $google2fa = app(Google2FA::class);
    $validCode = $google2fa->getCurrentOtp($user->two_factor_secret);

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/confirm', [
            'code' => $validCode,
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => ['recovery_codes'],
        ]);

    $recoveryCodes = $response->json('data.recovery_codes');
    expect($recoveryCodes)->toBeArray()->toHaveCount(8);

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue()
        ->and($user->two_factor_confirmed_at)->not->toBeNull();
});

it('rejects confirmation with an invalid TOTP code', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/enable')
        ->assertOk();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/confirm', [
            'code' => '000000',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors(['code']);
});

it('generates new recovery codes when 2fa is active', function (): void {
    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/enable')
        ->assertOk();

    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/confirm', [
            'code' => $validCode,
        ])
        ->assertOk();

    $response = $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/recovery-codes')
        ->assertOk()
        ->assertJsonPath('success', true);

    expect($response->json('data.recovery_codes'))->toHaveCount(8);
});

it('disables two factor authentication with user password confirmation', function (): void {
    $user = User::factory()->create([
        'password' => 'secret-password-123',
    ]);

    $twoFactor = app(TwoFactorServiceInterface::class);
    $twoFactor->enable($user);
    $user->refresh();
    $validCode = app(Google2FA::class)->getCurrentOtp($user->two_factor_secret);
    $twoFactor->confirm($user, $validCode);

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeTrue();

    $this->actingAs($user, 'sanctum')
        ->postJson('/api/v1/profile/two-factor/disable', [
            'password' => 'secret-password-123',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'Two factor authentication disabled successfully');

    $user->refresh();
    expect($user->hasTwoFactorEnabled())->toBeFalse()
        ->and($user->two_factor_secret)->toBeNull()
        ->and($user->two_factor_confirmed_at)->toBeNull();
});
