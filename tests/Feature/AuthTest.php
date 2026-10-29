<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a user with the default role and returns a token', function (): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'username' => 'jane_doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.user.username', 'jane_doe')
        ->assertJsonPath('data.user.email', 'jane@example.com')
        ->assertJsonPath('data.user.role', 'user')
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'user' => ['id', 'name', 'username', 'email', 'role'],
                'token',
            ],
        ]);

    $user = User::query()->where('email', 'jane@example.com')->firstOrFail();

    expect($user->username)->toBe('jane_doe')
        ->and($user->role)->toBe('user');
});

it('rejects registration with invalid data using the error envelope', function (): void {
    $this->postJson('/api/v1/auth/register', [])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure(['success', 'message', 'errors'])
        ->assertJsonValidationErrors(['name', 'username', 'email', 'password']);
});

it('rejects registration with a duplicate email', function (): void {
    User::factory()->create(['email' => 'jane@example.com']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'username' => 'jane_doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('email');
});

it('rejects registration with a duplicate username', function (): void {
    User::factory()->create(['username' => 'jane_doe']);

    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'username' => 'jane_doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('username');
});

it('rejects registration with an invalid username', function (string $username): void {
    $this->postJson('/api/v1/auth/register', [
        'name' => 'Jane Doe',
        'username' => $username,
        'email' => 'jane@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonValidationErrors('username');
})->with([
    'too short' => 'jd',
    'contains spaces' => 'jane doe',
    'contains symbols' => 'jane@doe',
]);

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

it('returns json when an unauthenticated request has a broad accept header', function (): void {
    $this->get('/api/v1/auth/me', ['Accept' => '*/*'])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.');
});

it('returns json when an unauthenticated browser accepts html', function (): void {
    $this->get('/api/v1/auth/me', ['Accept' => 'text/html,application/xhtml+xml'])
        ->assertUnauthorized()
        ->assertJsonPath('success', false)
        ->assertJsonPath('message', 'Unauthenticated.');
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

it('throttles repeated login attempts', function (): void {
    User::factory()->create([
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    for ($attempt = 1; $attempt <= 5; $attempt++) {
        $this->postJson('/api/v1/auth/login', [
            'email' => 'jane@example.com',
            'password' => 'password123',
        ])->assertOk();
    }

    $this->postJson('/api/v1/auth/login', [
        'email' => 'jane@example.com',
        'password' => 'password123',
    ])
        ->assertStatus(429)
        ->assertJsonPath('success', false);
});
