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
    private const int REGULAR_USER_COUNT = 10;

    /**
     * Seed one admin and ten verified regular users.
     *
     * Emails are deterministic — admin@example.com and user1..user10@example.com
     * — so every seeded account is known and repeatable, and each one uses the
     * password "password".
     */
    public function run(): void
    {
        $this->seedAccount('admin@example.com', 'Admin User', Roles::Admin);

        foreach (range(1, self::REGULAR_USER_COUNT) as $number) {
            $this->seedAccount("user{$number}@example.com", "User {$number}", Roles::User);
        }
    }

    /**
     * Create a verified account with the shared password and its single role.
     */
    private function seedAccount(string $email, string $name, Roles $role): void
    {
        User::factory()
            ->withRole($role)
            ->create([
                'name' => $name,
                'email' => $email,
                'password' => 'password',
            ]);
    }
}
