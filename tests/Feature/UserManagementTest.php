<?php

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

it('lists users with pagination for an admin', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    User::factory()->withRole(Roles::User)->count(5)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/users')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonStructure([
            'success',
            'message',
            'data' => [
                'items' => [
                    '*' => [
                        'id',
                        'name',
                        'email',
                        'avatar',
                        'role',
                        'email_verified',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'meta' => [
                    'current_page',
                    'per_page',
                    'total',
                    'last_page',
                ],
            ],
        ])
        ->assertJsonPath('data.meta.total', 6);
});

it('filters users by search and role', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $target = User::factory()->withRole(Roles::User)->create([
        'name' => 'Alice Special',
        'email' => 'alice@special.test',
    ]);
    User::factory()->withRole(Roles::User)->create([
        'name' => 'Bob Regular',
        'email' => 'bob@regular.test',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/users?search=Special&role=user')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.meta.total', 1)
        ->assertJsonPath('data.items.0.id', $target->id)
        ->assertJsonPath('data.items.0.name', 'Alice Special');
});

it('validates per_page query limits', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/users?per_page=1000')
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['per_page'],
        ]);

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/users?per_page=0')
        ->assertUnprocessable()
        ->assertJsonPath('success', false);
});

it('shows a single user', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.id', $user->id)
        ->assertJsonPath('data.name', $user->name)
        ->assertJsonPath('data.email', $user->email)
        ->assertJsonPath('data.role', 'user');
});

it('exposes the avatar as an absolute URL', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->withAvatar('avatars/jane.jpg')->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.avatar', Storage::url('avatars/jane.jpg'));
});

it('returns a null avatar for a user without one', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson("/api/v1/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('data.avatar', null);
});

it('stores an uploaded avatar when creating a user', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();

    $response = $this->actingAs($admin, 'sanctum')
        ->post('/api/v1/admin/users', [
            'name' => 'Avatar User',
            'email' => 'avatar@example.test',
            'password' => 'password',
            'role' => 'user',
            'avatar' => UploadedFile::fake()->image('avatar.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertCreated()
        ->assertJsonPath('success', true);

    $user = User::query()->where('email', 'avatar@example.test')->firstOrFail();

    expect($user->avatar)->not->toBeNull();
    Storage::assertExists($user->avatar);

    $response->assertJsonPath('data.avatar', Storage::url($user->avatar));
});

it('replaces the stored avatar and removes the previous file when updating', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->withAvatar('avatars/old.jpg')->create();
    Storage::put('avatars/old.jpg', 'old');

    $this->actingAs($admin, 'sanctum')
        ->put("/api/v1/admin/users/{$user->id}", [
            'avatar' => UploadedFile::fake()->image('new.jpg'),
        ], ['Accept' => 'application/json'])
        ->assertOk();

    $user->refresh();

    expect($user->avatar)->not->toBe('avatars/old.jpg');
    Storage::assertMissing('avatars/old.jpg');
    Storage::assertExists($user->avatar);
});

it('removes the stored avatar when the avatar is cleared', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->withAvatar('avatars/old.jpg')->create();
    Storage::put('avatars/old.jpg', 'old');

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/users/{$user->id}", ['avatar' => null])
        ->assertOk()
        ->assertJsonPath('data.avatar', null);

    expect($user->refresh()->avatar)->toBeNull();
    Storage::assertMissing('avatars/old.jpg');
});

it('removes the stored avatar when the user is deleted', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->withAvatar('avatars/gone.jpg')->create();
    Storage::put('avatars/gone.jpg', 'old');

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/users/{$user->id}")
        ->assertOk();

    Storage::assertMissing('avatars/gone.jpg');
});

it('rejects an avatar that is not an image', function (): void {
    Storage::fake();

    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->post('/api/v1/admin/users', [
            'name' => 'Bad Avatar',
            'email' => 'bad-avatar@example.test',
            'password' => 'password',
            'role' => 'user',
            'avatar' => UploadedFile::fake()->create('document.pdf', 10),
        ], ['Accept' => 'application/json'])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['avatar'],
        ]);

    $this->assertDatabaseMissing('users', ['email' => 'bad-avatar@example.test']);
});

it('creates a user with a role', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/users', [
            'name' => 'Charlie New',
            'email' => 'charlie@new.test',
            'password' => 'secret1234',
            'role' => 'admin',
        ])
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Charlie New')
        ->assertJsonPath('data.email', 'charlie@new.test')
        ->assertJsonPath('data.role', 'admin')
        ->assertJsonPath('data.email_verified', true);

    $this->assertDatabaseHas('users', ['email' => 'charlie@new.test']);
});

it('rejects invalid input when creating a user', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->postJson('/api/v1/admin/users', [
            'name' => '',
            'email' => 'not-an-email',
            'password' => 'short',
            'role' => 'invalid-role',
        ])
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['name', 'email', 'password', 'role'],
        ]);
});

it('updates a user', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->create([
        'name' => 'Original Name',
        'email' => 'original@test.com',
    ]);

    $this->actingAs($admin, 'sanctum')
        ->putJson("/api/v1/admin/users/{$user->id}", [
            'name' => 'Updated Name',
            'email' => 'updated@test.com',
            'role' => 'admin',
        ])
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.name', 'Updated Name')
        ->assertJsonPath('data.email', 'updated@test.com')
        ->assertJsonPath('data.role', 'admin');

    $this->assertDatabaseHas('users', [
        'id' => $user->id,
        'name' => 'Updated Name',
        'email' => 'updated@test.com',
    ]);
});

it('deletes a user', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/users/{$user->id}")
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('message', 'User deleted successfully');

    $this->assertDatabaseMissing('users', ['id' => $user->id]);
});

it('prevents an admin from deleting their own account', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();

    $this->actingAs($admin, 'sanctum')
        ->deleteJson("/api/v1/admin/users/{$admin->id}")
        ->assertUnprocessable()
        ->assertJsonPath('success', false)
        ->assertJsonStructure([
            'success',
            'message',
            'errors' => ['user'],
        ]);

    $this->assertDatabaseHas('users', ['id' => $admin->id]);
});

it('forbids non-admin users from accessing user management', function (): void {
    $user = User::factory()->withRole(Roles::User)->create();

    $this->actingAs($user, 'sanctum')
        ->getJson('/api/v1/admin/users')
        ->assertForbidden();
});

it('requires authentication for user management', function (): void {
    $this->getJson('/api/v1/admin/users')
        ->assertUnauthorized();
});
