<?php

declare(strict_types=1);

use App\Models\User;
use App\Notifications\PasswordResetOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

it('sends a password reset code to a registered email', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, PasswordResetOtpNotification::class);
});

it('does not reveal whether an email is registered', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'nobody@example.com'])
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertNothingSent();
});

it('resets the password with a valid code and revokes existing tokens', function (): void {
    Notification::fake();

    $user = User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'old-password',
    ]);
    $user->createToken('auth-token');

    $code = null;

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

    Notification::assertSentTo($user, PasswordResetOtpNotification::class, function (PasswordResetOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    expect($code)->toBeString();

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'jane@example.com',
        'otp' => $code,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();

    $this->assertDatabaseCount('personal_access_tokens', 0);
});

it('rejects an invalid reset code', function (): void {
    $user = User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'jane@example.com',
        'otp' => '00000',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('verifies a valid reset code without consuming it', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    $code = null;

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

    Notification::assertSentTo($user, PasswordResetOtpNotification::class, function (PasswordResetOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    expect($code)->toBeString();

    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'jane@example.com',
        'otp' => $code,
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'jane@example.com',
        'otp' => $code,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertOk()
        ->assertJsonPath('success', true);

    expect(Hash::check('new-password', $user->fresh()->password))->toBeTrue();
});

it('rejects an invalid code on the verify step', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'jane@example.com',
        'otp' => '00000',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('rejects a verify for an unregistered email', function (): void {
    $this->postJson('/api/v1/auth/password/verify', [
        'email' => 'nobody@example.com',
        'otp' => '00000',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('rejects a reset for an unregistered email', function (): void {
    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'nobody@example.com',
        'otp' => '00000',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('validates the verify payload', function (): void {
    $this->postJson('/api/v1/auth/password/verify', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'otp']);
});

it('rejects an expired reset code', function (): void {
    Notification::fake();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    $code = null;

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

    Notification::assertSentTo($user, PasswordResetOtpNotification::class, function (PasswordResetOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $this->travel(16)->minutes();

    $this->postJson('/api/v1/auth/password/reset', [
        'email' => 'jane@example.com',
        'otp' => $code,
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('locks a code out after too many wrong attempts', function (): void {
    config(['auth.rate_limits.password_reset' => 100]);

    Notification::fake();

    $user = User::factory()->create(['email' => 'jane@example.com']);

    $code = null;

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();

    Notification::assertSentTo($user, PasswordResetOtpNotification::class, function (PasswordResetOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    $payload = [
        'email' => 'jane@example.com',
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ];

    foreach (range(1, 5) as $i) {
        $this->postJson('/api/v1/auth/password/reset', [...$payload, 'otp' => '99999'])
            ->assertUnprocessable();
    }

    $this->postJson('/api/v1/auth/password/reset', [...$payload, 'otp' => $code])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('validates the reset payload', function (): void {
    $this->postJson('/api/v1/auth/password/reset', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['email', 'otp', 'password']);
});

it('throttles password reset requests per email', function (): void {
    Notification::fake();

    User::factory()->create(['email' => 'jane@example.com']);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])->assertOk();
    }

    $this->postJson('/api/v1/auth/password/forgot', ['email' => 'jane@example.com'])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});

it('renders the password reset email from a custom view', function (): void {
    $user = User::factory()->create();

    $mail = (new PasswordResetOtpNotification('54321', 15))->toMail($user);

    expect($mail->view)->toBe([
        'html' => 'emails.auth.reset-password-otp',
        'text' => 'emails.auth.reset-password-otp-text',
    ])
        ->and($mail->render())
        ->toContain('Reset your password')
        ->toContain('54321')
        ->toContain('15 minutes');
});
