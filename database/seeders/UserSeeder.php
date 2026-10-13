<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Roles;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Seed one admin and three regular users.
     */
    public function run(): void
    {
        User::factory()
            ->withRole(Roles::Admin)
            ->create([
                'name' => 'Admin User',
                'email' => 'admin@example.com',
            ]);

        User::factory()
            ->withRole(Roles::User)
            ->count(3)
            ->create();
    }
}
