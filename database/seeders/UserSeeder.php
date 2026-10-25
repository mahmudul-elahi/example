<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * The number of regular users seeded next to the admin account.
     */
    private const int REGULAR_USER_COUNT = 24;

    /**
     * Human names for the seeded regular accounts, in registration order.
     */
    private const array USER_NAMES = [
        'Ava Thompson',
        'Liam Carter',
        'Sophia Bennett',
        'Noah Hughes',
        'Mia Sullivan',
        'Ethan Brooks',
        'Isabella Reed',
        'Lucas Foster',
        'Amelia Hayes',
        'Mason Price',
        'Harper Morgan',
        'Elijah Ward',
        'Evelyn Ross',
        'James Cooper',
        'Charlotte Bailey',
        'Benjamin Rivera',
        'Abigail Powell',
        'Henry Simmons',
        'Ella Jenkins',
        'Alexander Perry',
        'Scarlett Long',
        'Daniel Patterson',
        'Grace Coleman',
        'Michael Russell',
    ];

    /**
     * Seed one admin and twenty-four verified regular users.
     *
     * Emails are deterministic — admin@example.com and user1..user24@example.com
     * — so every seeded account is known and repeatable, and each one uses the
     * password "password".
     */
    public function run(): void
    {
        $this->seedAccount('admin@example.com', 'Admin User', 'admin', Roles::Admin);

        foreach (range(1, self::REGULAR_USER_COUNT) as $number) {
            $this->seedAccount(
                "user{$number}@example.com",
                self::USER_NAMES[$number - 1],
                "user{$number}",
                Roles::User,
            );
        }
    }

    /**
     * Create a verified account with the shared password and its single role,
     * keeping an existing account usable when the seeder is rerun.
     */
    private function seedAccount(string $email, string $name, string $username, Roles $role): void
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null) {
            User::factory()
                ->withRole($role)
                ->create([
                    'name' => $name,
                    'username' => $username,
                    'email' => $email,
                    'password' => 'password',
                ]);

            return;
        }

        $user->setRole($role);

        if (! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();
        }
    }
}
