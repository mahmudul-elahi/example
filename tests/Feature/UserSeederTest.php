<?php

use App\Enums\Roles;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('seeds one admin and three regular users', function (): void {
    $this->seed(DatabaseSeeder::class);

    $users = User::all();

    expect($users)->toHaveCount(4)
        ->and($users->filter(fn (User $user): bool => $user->isAdmin()))->toHaveCount(1)
        ->and($users->filter(fn (User $user): bool => $user->role === Roles::User->value))->toHaveCount(3);
});
