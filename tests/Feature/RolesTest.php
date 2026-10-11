<?php

use App\Enums\Roles;
use App\Models\User;
use Database\Seeders\RoleSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

it('seeds exactly the admin and user roles', function (): void {
    $this->seed(RoleSeeder::class);

    expect(Role::query()->pluck('name')->all())->toBe(['admin', 'user']);
});

it('assigns a single role to a user', function (): void {
    $user = User::factory()->withRole(Roles::Admin)->create();

    expect($user->role)->toBe('admin')
        ->and($user->getRoleNames()->all())->toBe(['admin']);

    $user->setRole(Roles::User);

    expect($user->role)->toBe('user')
        ->and($user->getRoleNames()->all())->toBe(['user'])
        ->and($user->hasRole('admin'))->toBeFalse();
});

it('returns null for a user without a role', function (): void {
    $user = User::factory()->create();

    expect($user->role)->toBeNull();
});

it('guards admin-only routes with the role middleware', function (): void {
    $this->seed(RoleSeeder::class);

    Route::middleware(['auth:sanctum', 'role:admin'])
        ->get('/api/v1/_secret', fn (): array => ['ok' => true]);

    $this->actingAs(User::factory()->withRole(Roles::User)->create(), 'sanctum')
        ->getJson('/api/v1/_secret')
        ->assertForbidden();

    $this->actingAs(User::factory()->withRole(Roles::Admin)->create(), 'sanctum')
        ->getJson('/api/v1/_secret')
        ->assertOk();
});
