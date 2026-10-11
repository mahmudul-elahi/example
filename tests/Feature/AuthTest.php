<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user with the default role and returns a token', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonPath('data.user.role', 'user')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'email', 'role'],
                'token',
            ],
        ]);

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->role)->toBe('user');
});

it('rejects registration with invalid data using the error envelope', function (): void {
    $this->postJson('/api/v1/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('rejects registration with a duplicate email', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('email');
});

it('logs in with valid credentials', function (): void {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonStructure(['success', 'message', 'data' => ['user', 'token']]);
});

it('rejects invalid credentials with the error envelope', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'wrong-password',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors']);
});

it('returns the authenticated user', function (): void {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->json('data.token');

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.email', 'jane@example.com');
});

it('blocks unauthenticated access to the profile', function (): void {
    $this->getJson('/api/v1/auth/me')
        ->assertUnauthorized()
        ->assertJsonPath('success', false);
});

it('logs out and revokes the issued token', function (): void {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $token = $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])->json('data.token');

    $this->withToken($token)
        ->postJson('/api/v1/auth/logout')
        ->assertOk()
        ->assertJsonPath('success', true);

    $this->assertDatabaseCount('personal_access_tokens', 0);

    $this->app['auth']->forgetGuards();

    $this->withToken($token)
        ->getJson('/api/v1/auth/me')
        ->assertUnauthorized();
});
