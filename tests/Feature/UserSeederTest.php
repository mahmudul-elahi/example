<?php

use App\Enums\Roles;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('seeds one admin and ten regular users', function (): void {
    $this->seed(DatabaseSeeder::class);

    $users = User::all();

    expect($users)->toHaveCount(11)
        ->and($users->filter(fn (User $user): bool => $user->isAdmin()))->toHaveCount(1)
        ->and($users->filter(fn (User $user): bool => $user->role === Roles::User->value))->toHaveCount(10);
});

it('seeds a known, verified account for every user', function (): void {
    $this->seed(DatabaseSeeder::class);

    $emails = ['admin@example.com', ...array_map(
        fn (int $number): string => "user{$number}@example.com",
        range(1, 10),
    )];

    expect(User::query()->pluck('email')->all())->toEqualCanonicalizing($emails)
        ->and(User::query()->whereNull('email_verified_at')->exists())->toBeFalse()
        ->and(User::all()->every(fn (User $user): bool => Hash::check('password', $user->password)))->toBeTrue();
});
