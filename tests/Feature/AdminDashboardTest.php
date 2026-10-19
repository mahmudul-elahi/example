<?php

declare(strict_types=1);

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('returns dashboard data to an admin', function (): void {
    $admin = User::factory()->withRole(Roles::Admin)->create();
    User::factory()->withRole(Roles::User)->count(2)->create();
    User::factory()->unverified()->create();

    $this->actingAs($admin, 'sanctum')
        ->getJson('/api/v1/admin/dashboard')
        ->assertOk()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.users.users', 2)
        ->assertJsonPath('data.users.new_last_7_days', 4);
});

it('forbids non-admin users from the dashboard', function (): void {
    $this->actingAs(User::factory()->withRole(Roles::User)->create(), 'sanctum')
        ->getJson('/api/v1/admin/dashboard')
        ->assertForbidden();
});

it('requires authentication for the dashboard', function (): void {
    $this->getJson('/api/v1/admin/dashboard')
        ->assertUnauthorized();
});
