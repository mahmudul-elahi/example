<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\Roles;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Seed the exact two roles the application supports.
     */
    public function run(): void
    {
        foreach (Roles::cases() as $role) {
            Role::findOrCreate($role->value);
        }
    }
}
