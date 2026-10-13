<?php

use App\Models\User;
use App\Notifications\EmailVerificationOtpNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

it('sends a verification code on registration', function (): void {
    Notification::fake();

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonPath('data.user.email_verified', false);

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
});

it('resends a verification code to an authenticated unverified user', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/resend')
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertSentTo($user, EmailVerificationOtpNotification::class);
});

it('does not resend a code when the email is already verified', function (): void {
    Notification::fake();

    $user = User::factory()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/resend')
        ->assertOk()
        ->assertJsonPath('success', true);

    Notification::assertNothingSent();
});

it('verifies the email with a valid code', function (): void {
    Notification::fake();

    $user = User::factory()->unverified()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $code = null;

    $this->withToken($token)->postJson('/api/v1/auth/email/resend')->assertOk();

    Notification::assertSentTo($user, EmailVerificationOtpNotification::class, function (EmailVerificationOtpNotification $notification) use (&$code): bool {
        $code = $notification->code;

        return true;
    });

    expect($code)->toBeString();

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/verify', ['otp' => $code])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email_verified', true);

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

it('rejects an invalid verification code', function (): void {
    $user = User::factory()->unverified()->create();
    $token = $user->createToken('auth-token')->plainTextToken;

    $this->withToken($token)
        ->postJson('/api/v1/auth/email/verify', ['otp' => '00000'])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['otp' => 'The provided code is invalid or has expired.']);
});

it('gates protected routes until the email is verified', function (): void {
    Route::middleware(['auth:sanctum', 'verified'])
        ->get('/api/v1/_verified', fn (): array => ['ok' => true]);

    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/_verified')
        ->assertForbidden()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Your email address is not verified.');
});

it('allows protected routes once the email is verified', function (): void {
    Route::middleware(['auth:sanctum', 'verified'])
        ->get('/api/v1/_verified', fn (): array => ['ok' => true]);

    $user = User::factory()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/_verified')
        ->assertOk()
        ->assertJsonPath('ok', true);
});

it('exposes the verification status without blocking the profile', function (): void {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email_verified', false);
});

it('requires authentication for verification endpoints', function (): void {
    $this->postJson('/api/v1/auth/email/verify', ['otp' => '12345'])
        ->assertUnauthorized();

    $this->postJson('/api/v1/auth/email/resend')
        ->assertUnauthorized();
});

it('renders the verification email from a custom view', function (): void {
    $user = User::factory()->unverified()->create();

    $mail = (new EmailVerificationOtpNotification('12345', 15))->toMail($user);

    expect($mail->view)->toBe([
        'html' => 'emails.auth.verify-email-otp',
        'text' => 'emails.auth.verify-email-otp-text',
    ])
        ->and($mail->render())
        ->toContain('Verify your email address')
        ->toContain('12345')
        ->toContain('15 minutes');
});
