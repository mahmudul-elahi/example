<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Models\PetMemorial;
use App\Models\User;
use Database\Seeders\Concerns\HasSeedAuthors;
use Illuminate\Database\Seeder;

class PetMemorialSeeder extends Seeder
{
    use HasSeedAuthors;

    /**
     * Seed two published memorials per author plus a few drafts and private
     * memorials, which the public index filters out.
     */
    public function run(): void
    {
        if (PetMemorial::query()->exists()) {
            return;
        }

        $authors = $this->authors();

        $authors->each(function (User $author): void {
            PetMemorial::factory()
                ->count(2)
                ->forUser($author)
                ->create();
        });

        PetMemorial::factory()
            ->count(3)
            ->draft()
            ->forUser($authors->random())
            ->create();

        PetMemorial::factory()
            ->count(2)
            ->private()
            ->forUser($authors->random())
            ->create();
    }
}
